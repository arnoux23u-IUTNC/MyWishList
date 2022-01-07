<?php /** @noinspection PhpUndefinedVariableInspection */

/** @noinspection PhpUndefinedFieldInspection */

namespace mywishlist\mvc\controllers;

use Slim\Container;
use Slim\Http\{Request, Response};
use Slim\Exception\{NotFoundException, MethodNotAllowedException};
use mywishlist\Validator;
use mywishlist\mvc\Renderer;
use mywishlist\mvc\views\ItemView;
use mywishlist\mvc\models\{Item, User, Reserved};
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
        $liste = $this->item->liste;
        //Si l'item n'existe pas, on declenche une erreur
        if (empty($this->item))
            throw new NotFoundException($this->request, $this->response);
        //On verifie donc si l'item est attribué à une liste et n'est pas reservé. Si non : on déclenche une erreur
        $reserved = Reserved::where("item_id", "LIKE", $this->item->id)->first();
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
     * Control the deletion of an item
     * @return Response
     * @throws ForbiddenException
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    public function delete(): Response
    {
        $liste = $this->item->liste;
        //Si l'item n'existe pas, on declenche une erreur
        if (empty($this->item))
            throw new NotFoundException($this->request, $this->response);
        //On verifie donc si l'item est attribué à une liste et n'est pas reservé. Si non : on déclenche une erreur
        $reserved = Reserved::where("item_id", "LIKE", $this->item->id)->first();
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
        $liste = $this->item->liste;
        //Si l'item n'existe pas, on declenche une erreur
        if (empty($this->item))
            throw new NotFoundException($this->request, $this->response);
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
}