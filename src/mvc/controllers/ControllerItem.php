<?php /** @noinspection PhpUndefinedVariableInspection */

/** @noinspection PhpUndefinedFieldInspection */

namespace mywishlist\mvc\controllers;

use Slim\Container;
use Slim\Http\{Request, Response};
use Slim\Exception\{NotFoundException, MethodNotAllowedException};
use mywishlist\Validator;
use mywishlist\mvc\Renderer;
use mywishlist\mvc\views\ItemView;
use mywishlist\mvc\models\{Item, User, Reservation, Cagnotte, Participation, UserTemporaryResolver};
use mywishlist\exceptions\ForbiddenException;

/**
 * Class ControllerItem
 * Controller for Item Model
 * @author Guillaume ARNOUX
 * @package mywishlist\mvc\controllers
 */
class ControllerItem
{

    private Container $container;
    /**
     * @var User|null User associated to the Controller
     */
    private ?User $user;
    /**
     * @var Item|null Item associated to the Controller
     */
    private ?Item $item;
    private ItemView $renderer;
    private Request $request;
    private Response $response;
    private array $args;

    /**
     * ControllerItem constructor
     * @param Container $c
     * @param Request $request
     * @param Response $response
     * @param array $args
     */
    public function __construct(Container $c, Request $request, Response $response, array $args)
    {
        $this->container = $c;
        $this->item = Item::where("id", "LIKE", filter_var($args['id'], FILTER_SANITIZE_NUMBER_INT))->first();
        $this->user = !empty($_SESSION['USER_ID']) ? User::find($_SESSION['USER_ID']) : new User();
        $this->renderer = new ItemView($this->container, $this->item, $request);
        $this->request = $request;
        $this->response = $response;
        $this->args = $args;
    }

    /**
     * Control edition of an item
     * @return Response
     * @throws ForbiddenException
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    public function edit(): Response
    {
        //Si l'item n'existe pas, on declenche une erreur
        if (empty($this->item))
            throw new NotFoundException($this->request, $this->response);
        $liste = $this->item->liste;
        //On verifie donc si l'item est attribué à une liste et n'est pas reservé. Si non : on déclenche une erreur
        $reserved = Reservation::where("item_id", "LIKE", $this->item->id)->first();
        if (!$this->user->isAdmin() && (empty($liste) || !empty($reserved)))
            throw new ForbiddenException($this->container->lang['exception_forbidden'], $this->container->lang['exception_ressource_not_allowed']);
        switch ($this->request->getMethod()) {
            case 'GET':
                //Si l'utilisateur est admin, on lui affiche le formulaire d'edition
                if ($this->user->isAdmin())
                    return $this->response->write($this->renderer->render(Renderer::EDIT, Renderer::ADMIN_MODE));
                //Si l'utilisateur peut interragir avec la liste, on lui affiche le formulaire d'edition
                if ($this->user->canInteractWithList($liste))
                    return $this->response->write($this->renderer->render(Renderer::EDIT, Renderer::OWNER_MODE));
                //Sinon, on demande l'authentification
                return $this->response->write($this->renderer->render(Renderer::REQUEST_AUTH));
            case 'POST':
                /*Trois cas de figure :
                - L'utilisateur est admin ou peut modifier la liste, on passe a l'edition
                - L'utilisateur est inconnu, et a saisi le token privé
                - L'utilisateur est inconnu, et n'a pas saisi le token privé, on lui affiche le formulaire d'authentification*/
                if (!$this->user->isAdmin() && !$this->user->canInteractWithList($liste) && empty($this->request->getParsedBodyParam('private_key')))
                    return $this->response->write($this->renderer->render(Renderer::REQUEST_AUTH));
                if (!$this->user->isAdmin() && !$this->user->canInteractWithList($liste) && !password_verify(filter_var($this->request->getParsedBodyParam('private_key') ?? "", FILTER_SANITIZE_STRING), $liste->private_key))
                    return $this->response->withRedirect($this->container->router->pathFor('items_edit_id', ['id' => $liste->no], ["info" => "errtoken"]));
                //On verifie une valeur provenant directement du formulaire. Soit on valide, soit on montre le formulaire.
                if (!$this->user->isAdmin() && !$this->user->canInteractWithList($liste) && (empty($this->request->getParsedBodyParam('auth')) || filter_var($this->request->getParsedBodyParam('auth'), FILTER_SANITIZE_STRING) !== '1'))
                    return $this->response->write($this->renderer->render(Renderer::EDIT));
                $file = $this->request->getUploadedFiles()['file_img'];
                //Si un fichier est uploadé, on le traite
                if (!empty($file->getClientFilename())) {
                    $finfo = [pathinfo($file->getClientFilename(), PATHINFO_FILENAME), strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION))];
                    $info = Validator::validateFile($this->container, $file, $finfo, "item");
                }
                //On modifie ensuite l'item
                $this->item->update([
                    'liste_id' => $liste->no,
                    'nom' => filter_var($this->request->getParsedBodyParam('name'), FILTER_SANITIZE_STRING),
                    'descr' => filter_var($this->request->getParsedBodyParam('description'), FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                    'tarif' => filter_var($this->request->getParsedBodyParam('price'), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
                    'url' => filter_var($this->request->getParsedBodyParam('url'), FILTER_SANITIZE_URL),
                    'img' => $this->request->getParsedBodyParam('type') === "link" ? filter_var($this->request->getParsedBodyParam('url_img'), FILTER_SANITIZE_URL) : ($this->request->getParsedBodyParam('type') === "upload" ? ($info === "ok" ? $finfo[0] . "." . $finfo[1] : $this->item->img) : $this->item->img)
                ]);
                return $this->response->withRedirect($this->container->router->pathFor('lists_show_id', ["id" => $liste->no], ["public_key" => $liste->public_key, "state" => "modItem", "info" => $info]));
            default:
                throw new MethodNotAllowedException($this->request, $this->response, ['GET', 'POST']);
        }
    }
  
    /**
     * Control creation of pot for an item
     * @return Response
     * @throws ForbiddenException
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    private function createPot(): Response
    {
        //Si l'item n'existe pas, on declenche une erreur
        if (empty($this->item))
            throw new NotFoundException($this->request, $this->response);
        $liste = $this->item->liste;
        //Si il existe deja une cagnotte pour l'item, on renvoie vers la page de l'item
        $pot = Cagnotte::find($this->item->id);
        if (!empty($pot->montant))
            return $this->response->withRedirect($this->container->router->pathFor('items_show_id', ["id" => $this->item->id], ["state" => "alreadyPot"]));
        //On verifie donc si l'item est attribué à une liste. Si non : on déclenche une erreur
        if (!$this->user->isAdmin() && empty($liste))
            throw new ForbiddenException($this->container->lang['exception_forbidden'], $this->container->lang['exception_ressource_not_allowed']);
        switch ($this->request->getMethod()) {
            case 'GET':
                //Si l'utilisateur est admin, on lui affiche le formulaire de création
                if ($this->user->isAdmin())
                    return $this->response->write($this->renderer->render(Renderer::POT_CREATE, Renderer::ADMIN_MODE));
                //Si l'utilisateur peut interragir avec la liste, on lui affiche le formulaire d'edition
                if ($this->user->canInteractWithList($liste))
                    return $this->response->write($this->renderer->render(Renderer::POT_CREATE, Renderer::OWNER_MODE));
                //Sinon, on demande l'authentification
                return $this->response->write($this->renderer->render(Renderer::REQUEST_AUTH));
            case 'POST':
                /*Trois cas de figure :
                - L'utilisateur est admin ou peut modifier la liste, on passe a l'edition
                - L'utilisateur est inconnu, et a saisi le token privé
                - L'utilisateur est inconnu, et n'a pas saisi le token privé, on lui affiche le formulaire d'authentification*/
                if (!$this->user->isAdmin() && !$this->user->canInteractWithList($liste) && empty($this->request->getParsedBodyParam('private_key')))
                    return $this->response->write($this->renderer->render(Renderer::REQUEST_AUTH));
                if (!$this->user->isAdmin() && !$this->user->canInteractWithList($liste) && !password_verify(filter_var($this->request->getParsedBodyParam('private_key') ?? "", FILTER_SANITIZE_STRING), $liste->private_key))
                    return $this->response->withRedirect($this->container->router->pathFor('lists_show_id', ['id' => $liste->no], ["info" => "errtoken"]));
                //On verifie une valeur provenant directement du formulaire. Soit on valide, soit on montre le formulaire.
                if (!$this->user->isAdmin() && !$this->user->canInteractWithList($liste) && (empty($this->request->getParsedBodyParam('auth')) || filter_var($this->request->getParsedBodyParam('auth'), FILTER_SANITIZE_STRING) !== '1'))
                    return $this->response->write($this->renderer->render(Renderer::POT_CREATE));
                $amount = filter_var($this->request->getParsedBodyParam('amount'), FILTER_SANITIZE_NUMBER_FLOAT);
                if(empty($amount))
                    return $this->response->withRedirect($this->container->router->pathFor('items_pot_id', ['id' => $this->item->id, 'action' => 'create'], ["info" => "errAmount"]));
                //Création de la cagnotte
                $pot = new Cagnotte();
                $pot->item_id = $this->item->id;
                $pot->montant = $amount;
                $pot->limite = $this->request->getParsedBodyParam('expiration') !== "" ? filter_var($this->request->getParsedBodyParam('expiration'), FILTER_SANITIZE_STRING) : NULL;
                $pot->save();
                return $this->response->withRedirect($this->container->router->pathFor('lists_show_id', ["id" => $liste->no], ["public_key" => $liste->public_key, "info" => "createdPot"]));
            default:
                throw new MethodNotAllowedException($this->request, $this->response, ['GET', 'POST']);
        }
    }

    /**
     * Control creation of pot for an item
     * @return Response
     * @throws ForbiddenException
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    private function deletePot(): Response
    {
        //Si l'item n'existe pas, on declenche une erreur
        if (empty($this->item))
            throw new NotFoundException($this->request, $this->response);
        $liste = $this->item->liste;
        //Si il n'existe pas de cagnotte pour l'item, on renvoie vers la page de l'item
        $pot = Cagnotte::find($this->item->id);
        if (empty($pot->montant))
            return $this->response->withRedirect($this->container->router->pathFor('items_show_id', ["id" => $this->item->id], ["state" => "noPot"]));
        //On verifie donc si l'item est attribué à une liste. Si non : on déclenche une erreur
        if (!$this->user->isAdmin() && empty($liste))
            throw new ForbiddenException($this->container->lang['exception_forbidden'], $this->container->lang['exception_ressource_not_allowed']);
        switch ($this->request->getMethod()) {
            case 'GET':
                //Si l'utilisateur n'est pas admin et ne peut pas interagir avec la liste, on lui demande la clé privée
                if (!$this->user->isAdmin() && !$this->user->canInteractWithList($liste))
                    return $this->response->write($this->renderer->render(Renderer::REQUEST_AUTH));
            case 'POST':
                /*Trois cas de figure :
                - L'utilisateur est admin ou peut modifier la liste, on passe a l'edition
                - L'utilisateur est inconnu, et a saisi le token privé
                - L'utilisateur est inconnu, et n'a pas saisi le token privé, on lui affiche le formulaire d'authentification*/
                if (!$this->user->isAdmin() && !$this->user->canInteractWithList($liste) && empty($this->request->getParsedBodyParam('private_key')))
                    return $this->response->write($this->renderer->render(Renderer::REQUEST_AUTH));
                if (!$this->user->isAdmin() && !$this->user->canInteractWithList($liste) && !password_verify(filter_var($this->request->getParsedBodyParam('private_key') ?? "", FILTER_SANITIZE_STRING), $liste->private_key))
                    return $this->response->withRedirect($this->container->router->pathFor('lists_show_id', ['id' => $liste->no], ["info" => "errtoken"]));
                //On supprime la cagnotte
                $pot->remove();
                return $this->response->withRedirect($this->container->router->pathFor('lists_show_id', ["id" => $liste->no], ["public_key" => $liste->public_key, "info" => "deletedPot"]));
            default:
                throw new MethodNotAllowedException($this->request, $this->response, ['GET', 'POST']);
        }
    }

    /**
     * Control editing of pot for an item
     * @return Response
     * @throws ForbiddenException
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    private function participatePot(): Response
    {
        //Si l'item n'existe pas, on declenche une erreur
        if (empty($this->item))
            throw new NotFoundException($this->request, $this->response);
        $liste = $this->item->liste;
        //On verifie donc si l'item est attribué à une liste. Si non : on déclenche une erreur
        if (!$this->user->isAdmin() && empty($liste))
            throw new ForbiddenException($this->container->lang['exception_forbidden'], $this->container->lang['exception_ressource_not_allowed']);
        //Si il n'existe pas de cagnotte pour l'item, on renvoie vers la page de l'item
        $pot = Cagnotte::find($this->item->id);
        if (empty($pot->montant))
            return $this->response->withRedirect($this->container->router->pathFor('items_show_id', ["id" => $this->item->id], ["state" => "noPot"]));
        if($pot->isExpired() || $pot->totalAmount() >= $pot->montant)
            return $this->response->withRedirect($this->container->router->pathFor('items_show_id', ["id" => $this->item->id], ["state" => "errPot"]));
        switch ($this->request->getMethod()) {
            case 'GET':
                return $this->response->write($this->renderer->render(Renderer::POT_PARTICIPATE));
            case 'POST':
                /*Deux cas de figure :
                - L'utilisateur est admin ou peut modifier la liste, on passe a l'edition
                - L'utilisateur est inconnu, on verifie le token provenant du formulaire*/
                $public_key = filter_var($this->request->getParsedBodyParam('public_key'), FILTER_SANITIZE_STRING);
                if (!$this->user->isAdmin() && !$this->user->canInteractWithList($liste) && (empty($public_key) || $public_key !== $liste->public_key))
                    throw new ForbiddenException(message: $this->container->lang['exception_ressource_not_allowed']);
                $amount = filter_var($this->request->getParsedBodyParam('amount'), FILTER_SANITIZE_NUMBER_FLOAT);
                $email = filter_var($this->request->getParsedBodyParam('email'), FILTER_SANITIZE_EMAIL);
                if(empty($amount) || $amount < 1 || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || $amount > $pot->reste())
                    return $this->response->withRedirect($this->container->router->pathFor('lists_show_id', ['id' => $liste->no], ["public_key" => $liste->public_key, "info" => "errPot"]));
                if(!empty(Participation::whereCagnotteItemidAndUserEmail($pot->item_id, $email)->first()))
                    return $this->response->withRedirect($this->container->router->pathFor('lists_show_id', ['id' => $liste->no], ["public_key" => $liste->public_key, "info" => "alreadyPot"]));
                $p = new Participation();
                $p->cagnotte_itemid = $this->item->id;
                $p->montant = $amount;
                $p->user_email = $email;
                $p->save();                
                return $this->response->withRedirect($this->container->router->pathFor('lists_show_id', ["id" => $liste->no], ["public_key" => $liste->public_key, "info" => "potOk"]));
            default:
                throw new MethodNotAllowedException($this->request, $this->response, ['GET', 'POST']);
        }
    }

    /**
     * Control reservation of an item
     * @return Response
     * @throws ForbiddenException
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    public function reserve(): Response
    {
        //Si l'item n'existe pas, on declenche une erreur
        if (empty($this->item))
            throw new NotFoundException($this->request, $this->response);
        $liste = $this->item->liste;
        //On verifie donc si l'item est attribué à une liste. Si non : on déclenche une erreur
        if (!$this->user->isAdmin() && empty($liste))
            throw new ForbiddenException($this->container->lang['exception_forbidden'], $this->container->lang['exception_ressource_not_allowed']);
        if($this->request->getMethod() != 'POST')
            throw new MethodNotAllowedException($this->request, $this->response, ['POST']);
        /*Deux cas de figure :
        - L'utilisateur est admin ou peut modifier la liste, on passe a l'edition
        - L'utilisateur est inconnu, on verifie le token provenant du formulaire*/
        $public_key = filter_var($this->request->getQueryParam('public_key', ''), FILTER_SANITIZE_STRING);
        if (!$this->user->isAdmin() && !$this->user->canInteractWithList($liste) && (empty($public_key) || $public_key !== $liste->public_key))
            throw new ForbiddenException(message: $this->container->lang['exception_ressource_not_allowed']);
        if (empty($this->request->getParsedBodyParam('sendBtn')) || filter_var($this->request->getParsedBodyParam('sendBtn'), FILTER_SANITIZE_STRING) !== 'OK')
            return $this->response->write($this->renderer->render(Renderer::RESERVATION_FORM));
        $email = filter_var($this->request->getParsedBodyParam('email'), FILTER_SANITIZE_EMAIL);
        if(!filter_var($email, FILTER_VALIDATE_EMAIL))
            return $this->response->withRedirect($this->container->router->pathFor('lists_show_id', ['id' => $liste->no], ["state" => "error"]));
        //Si l'utilisateur n'est pas loggé, on enregistre son email dans un cookie pendant 1h
        if(empty($_SESSION["LOGGED_IN"]))
            setcookie("user_email", $email, time() + 3600, "/", "");
        $res = new Reservation();
        $res->item_id = $this->item->id;
        $res->user_email = $email;
        $res->message = $this->request->getParsedBodyParam('message') != "" ? filter_var($this->request->getParsedBodyParam('message'), FILTER_SANITIZE_STRING) : NULL;
        $res->save();
        return $this->response->withRedirect($this->container->router->pathFor('lists_show_id', ["id" => $liste->no], ["public_key" => $liste->public_key, "state" => "reserved"]));
    }

    /**
     * Control the deletion of an item
     * @return Response
     * @throws ForbiddenException
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    public function delete(): Response
    {
        //Si l'item n'existe pas, on declenche une erreur
        if (empty($this->item))
            throw new NotFoundException($this->request, $this->response);
        $liste = $this->item->liste;
        //On verifie donc si l'item est attribué à une liste et n'est pas reservé. Si non : on déclenche une erreur
        $reserved = Reservation::where("item_id", "LIKE", $this->item->id)->first();
        if (!$this->user->isAdmin() && (empty($liste) || (!empty($reserved) && !$liste->isExpired())))
            throw new ForbiddenException($this->container->lang['exception_forbidden'], $this->container->lang['exception_ressource_not_allowed']);
        //On genere une exception si la requete n'est pas de type POST
        if ($this->request->getMethod() !== 'POST')
            throw new MethodNotAllowedException($this->request, $this->response, ['POST']);
        //On verifie qu'une clé privée ait bien été saisie et qu'elle correspond à celle de la liste
        if (!$this->user->isAdmin() && !$this->user->canInteractWithList($liste) && (empty($this->request->getParsedBodyParam('private_key')) || !password_verify(filter_var($this->request->getParsedBodyParam('private_key') ?? "", FILTER_SANITIZE_STRING), $liste->private_key)))
            return $this->response->withRedirect($this->container->router->pathFor('lists_show_id', ['id' => $liste->no], ["public_key" => filter_var($this->request->getQueryParam('public_key'), FILTER_SANITIZE_STRING), "info" => "errtoken"]));
        //On supprime l'item
        $this->item->delete();
        return $this->response->withRedirect($this->container->router->pathFor('lists_show_id', ["id" => $liste->no], ["public_key" => $liste->public_key, "state" => "delItem"]));
    }

    /**
     * Control the display of an item
     * @return Response
     * @throws ForbiddenException
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    public function show(): Response
    {
        //Si l'item n'existe pas, on declenche une erreur
        if (empty($this->item))
            throw new NotFoundException($this->request, $this->response);
        $liste = $this->item->liste;
        //Si l'utilisateur est admin, on lui montre l'item
        if ($this->user->isAdmin())
            return $this->response->write($this->renderer->render(Renderer::SHOW, Renderer::ADMIN_MODE));
        /*On considere ici que l'utilisateur n'est pas admin et que l'item existe
        On verifie donc si l'item est attribué à une liste. Si non : on déclenche une erreur*/
        if (empty($liste))
            throw new ForbiddenException($this->container->lang['exception_forbidden'], $this->container->lang['exception_ressource_not_allowed']);
        //Si l'utilisateur est le propriétaire de la liste, on lui montre l'item
        if ($this->user->canInteractWithList($liste))
            return $this->response->write($this->renderer->render(Renderer::SHOW, Renderer::OWNER_MODE));
        //A ce point, on considere que l'utilisateur est inconnu, on doit donc s'assurer qu'il precise le token de la liste si cette derniere en est pourvue
        switch ($this->request->getMethod()) {
            case 'GET':
                //On genere une exception car un utilisateur inconnu ne peut pas voir un item sans passer par la liste
                throw new ForbiddenException($this->container->lang['exception_forbidden'], $this->container->lang['exception_ressource_not_allowed']);
            case 'POST':
                //Si la liste n'est pas publiée, ou que les variables d'accès ne correspondent pas, on genere une exception?? php
                if ((!$liste->isPublished()) || ($liste->no != filter_var($this->request->getParsedBodyParam('liste_id'), FILTER_SANITIZE_STRING) ?? "") || (!empty($liste->public_key) && $liste->public_key !== filter_var($this->request->getParsedBodyParam('public_key') ?? "", FILTER_SANITIZE_STRING)))
                    throw new ForbiddenException($this->container->lang['exception_forbidden'], $this->container->lang['exception_ressource_not_allowed']);
                //Sinon, on affiche la liste
                return $this->response->write($this->renderer->render(Renderer::SHOW));
            default:
                throw new MethodNotAllowedException($this->request, $this->response, ['GET', 'POST']);
        }
    }

    /**
     * Process request for the pot of an item
     * @return Response
     */
    public function actionPot(): Response
    {
        return match ($this->args['action']) {
            'delete' => $this->deletePot(),
            'participate' => $this->participatePot(),
            'create' => $this->createPot(),
            default => throw new NotFoundException($this->request, $this->response),
        };
    }

}