<?php /** @noinspection PhpUndefinedVariableInspection */

/** @noinspection PhpUndefinedFieldInspection */

namespace mywishlist\mvc\controllers;

use Exception;
use Slim\Container;
use Slim\Http\{Request, Response};
use Slim\Exception\{NotFoundException, MethodNotAllowedException};
use Tokenly\TokenGenerator\TokenGenerator;
use mywishlist\Validator;
use mywishlist\mvc\Renderer;
use mywishlist\mvc\views\ListView;
use mywishlist\exceptions\ForbiddenException;
use mywishlist\mvc\models\{Liste, Message, User, UserTemporaryResolver, Reservation, Participation, Cagnotte};

/**
 * Class ControllerList
 * Controller for Liste Model
 * @author Guillaume ARNOUX
 * @package mywishlist\mvc\controllers
 */
class ControllerList
{

    private Container $container;
    /**
     * @var User|null User associated to the controller
     */
    private ?User $user;
    /**
     * @var Liste|null Liste associated to the controller
     */
    private ?Liste $liste;
    private ListView $renderer;
    private Request $request;
    private Response $response;

    /**
     * ControllerList constructor
     * @param Container $c
     * @param Request $request
     * @param Response $response
     * @param array $args
     */
    public function __construct(Container $c, Request $request, Response $response, array $args)
    {
        $this->container = $c;
        $this->liste = Liste::where("no", "LIKE", filter_var($args['id'] ?? "", FILTER_SANITIZE_NUMBER_INT))->first();
        $this->user = !empty($_SESSION['USER_ID']) ? User::find($_SESSION['USER_ID']) : new User();
        $this->renderer = new ListView($this->container, $this->liste, $request);
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Control edition of a list
     * @return Response
     * @throws MethodNotAllowedException
     * @throws NotFoundException|ForbiddenException
     */
    public function edit(): Response
    {
        //Si la liste n'existe pas, on declenche une erreur
        if (empty($this->liste))
            throw new NotFoundException($this->request, $this->response);
        switch ($this->request->getMethod()) {
            case 'GET':
                //Si l'utilisateur est admin, on lui affiche le formulaire d'edition
                if ($this->user->isAdmin())
                    return $this->response->write($this->renderer->render(Renderer::EDIT, Renderer::ADMIN_MODE));
                //Si l'utilisateur peut interragir avec la liste, on lui affiche le formulaire d'edition
                if ($this->user->canInteractWithList($this->liste))
                    return $this->response->write($this->renderer->render(Renderer::EDIT, Renderer::OWNER_MODE));
                //Sinon, on demande l'authentification
                return $this->response->write($this->renderer->render(Renderer::REQUEST_AUTH));
            case 'POST':
                /*Trois cas de figure :
                - L'utilisateur est admin ou peut modifier la liste, on passe a l'edition
                - L'utilisateur est inconnu, et a saisi le token priv??
                - L'utilisateur est inconnu, et n'a pas saisi le token priv??, on lui affiche le formulaire d'authentification*/
                if (!$this->user->isAdmin() && !$this->user->canInteractWithList($this->liste) && empty($this->request->getParsedBodyParam('private_key')))
                    return $this->response->write($this->renderer->render(Renderer::REQUEST_AUTH));
                if (!$this->user->isAdmin() && !$this->user->canInteractWithList($this->liste) && !password_verify(filter_var($this->request->getParsedBodyParam('private_key') ?? "", FILTER_SANITIZE_STRING), $this->liste->private_key))
                    return $this->response->withRedirect($this->container->router->pathFor('lists_edit_id', ['id' => $this->liste->no], ["info" => "errtoken"]));
                //On verifie une valeur provenant directement du formulaire. Soit on valide, soit on montre le formulaire.
                if (!$this->user->isAdmin() && !$this->user->canInteractWithList($this->liste) && (empty($this->request->getParsedBodyParam('auth')) || filter_var($this->request->getParsedBodyParam('auth'), FILTER_SANITIZE_STRING) !== '1'))
                    return $this->response->write($this->renderer->render(Renderer::EDIT));
                $this->liste->update([
                    'titre' => filter_var($this->request->getParsedBodyParam('titre'), FILTER_SANITIZE_STRING),
                    'description' => filter_var($this->request->getParsedBodyParam('description'), FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                    'expiration' => $this->request->getParsedBodyParam('expiration') !== "" ? filter_var($this->request->getParsedBodyParam('expiration'), FILTER_SANITIZE_STRING) : NULL,
                    'public_key' => filter_var($this->request->getParsedBodyParam('public_key'), FILTER_SANITIZE_STRING),
                    'is_public' => filter_var($this->request->getParsedBodyParam('conf') ?? 0, FILTER_SANITIZE_NUMBER_INT),
                ]);
                return $this->response->withRedirect($this->container->router->pathFor('lists_show_id', ["id" => $this->liste->no], ["public_key" => $this->liste->public_key, "state" => "update"]));
            default:
                throw new MethodNotAllowedException($this->request, $this->response, ['GET', 'POST']);
        }
    }

    /**
     * Control adding message to a list
     * @return Response
     * @throws MethodNotAllowedException
     * @throws NotFoundException|ForbiddenException
     */
    public function addMessage(): Response
    {
        //Si la liste n'existe pas, on declenche une erreur
        if (empty($this->liste))
            throw new NotFoundException($this->request, $this->response);
        if ($this->request->getMethod() !== 'POST')
            throw new MethodNotAllowedException($this->request, $this->response, ['POST']);
        //On r??cup??re le mail et le message
        if (!filter_var($this->request->getParsedBodyParam('email'), FILTER_VALIDATE_EMAIL))
            throw new ForbiddenException(message: $this->container->lang['exception_ressource_not_allowed']);
        $mail = filter_var($this->request->getParsedBodyParam('email'), FILTER_SANITIZE_EMAIL);
        $message = filter_var($this->request->getParsedBodyParam('message'), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (ltrim($message) !== "") {
            $msg = new Message();
            $msg->list_id = $this->liste->no;
            $msg->user_email = $mail;
            $msg->message = $message;
            $msg->date = date("Y-m-d H:i:s");
            $msg->save();
        }
        return $this->response->withRedirect($this->container->router->pathFor('lists_show_id', ["id" => $this->liste->no], ["public_key" => $this->liste->public_key, "state" => "update"]));
    }

    /**
     * Control adding an item to a list
     * @return Response
     * @throws MethodNotAllowedException
     * @throws NotFoundException|ForbiddenException
     */
    public function addItem(): Response
    {
        //Si la liste n'existe pas, on declenche une erreur
        if (empty($this->liste))
            throw new NotFoundException($this->request, $this->response);
        switch ($this->request->getMethod()) {
            case 'GET':
                //Si l'utilisateur est admin, on lui affiche le formulaire d'ajout
                if ($this->user->isAdmin())
                    return $this->response->write($this->renderer->render(Renderer::EDIT_ADD_ITEM, Renderer::ADMIN_MODE));
                //Si l'utilisateur peut interragir avec la liste, on lui affiche le formulaire d'ajout
                if ($this->user->canInteractWithList($this->liste))
                    return $this->response->write($this->renderer->render(Renderer::EDIT_ADD_ITEM, Renderer::OWNER_MODE));
                //Sinon, on demande l'authentification
                return $this->response->write($this->renderer->render(Renderer::REQUEST_AUTH));
            case 'POST':
                /*Trois cas de figure :
                - L'utilisateur est admin ou peut modifier la liste, on passe a l'edition
                - L'utilisateur est inconnu, et a saisi le token priv??
                - L'utilisateur est inconnu, et n'a pas saisi le token priv??, on lui affiche le formulaire d'authentification*/
                if (!$this->user->isAdmin() && !$this->user->canInteractWithList($this->liste) && empty($this->request->getParsedBodyParam('private_key')))
                    return $this->response->write($this->renderer->render(Renderer::REQUEST_AUTH));
                if (!$this->user->isAdmin() && !$this->user->canInteractWithList($this->liste) && !password_verify(filter_var($this->request->getParsedBodyParam('private_key') ?? "", FILTER_SANITIZE_STRING), $this->liste->private_key))
                    return $this->response->withRedirect($this->container->router->pathFor('lists_edit_id', ['id' => $this->liste->no], ["info" => "errtoken"]));
                //On verifie une valeur provenant directement du formulaire. Soit on valide, soit on montre le formulaire.
                if (!$this->user->isAdmin() && !$this->user->canInteractWithList($this->liste) && (empty($this->request->getParsedBodyParam('auth')) || filter_var($this->request->getParsedBodyParam('auth'), FILTER_SANITIZE_STRING) !== '1'))
                    return $this->response->write($this->renderer->render(Renderer::EDIT_ADD_ITEM));
                $file = $this->request->getUploadedFiles()['file_img'];
                //Si un fichier est upload??, on le traite
                if (!empty($file->getClientFilename())) {
                    $finfo = [pathinfo($file->getClientFilename(), PATHINFO_FILENAME), strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION))];
                    $info = Validator::validateFile($this->container, $file, $finfo, "item");
                }
                //On ajoute ensuite l'item a la liste
                $this->liste->items()->create([
                    'liste_id' => $this->liste->no,
                    'nom' => filter_var($this->request->getParsedBodyParam('item_name'), FILTER_SANITIZE_STRING),
                    'descr' => filter_var($this->request->getParsedBodyParam('description'), FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                    'tarif' => filter_var($this->request->getParsedBodyParam('price'), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
                    'url' => filter_var($this->request->getParsedBodyParam('url'), FILTER_SANITIZE_URL),
                    'img' => $this->request->getParsedBodyParam('type') === "link" ? filter_var($this->request->getParsedBodyParam('url_img'), FILTER_SANITIZE_URL) : ($this->request->getParsedBodyParam('type') === "upload" ? ($info === "ok" ? $finfo[0] . "." . $finfo[1] : NULL) : NULL)
                ]);
                return $this->response->withRedirect($this->container->router->pathFor('lists_show_id', ["id" => $this->liste->no], ["public_key" => $this->liste->public_key, "state" => "newItem", "info" => $info]));
            default:
                throw new MethodNotAllowedException($this->request, $this->response, ['GET', 'POST']);
        }
    }

    /**
     * Control deletion of a list
     * @return Response
     * @throws MethodNotAllowedException
     * @throws Exception
     */
    public function delete(): Response
    {
        //Si la liste n'existe pas, on declenche une erreur
        if (empty($this->liste))
            throw new NotFoundException($this->request, $this->response);
        switch ($this->request->getMethod()) {
            case 'GET':
                //Si l'utilisateur n'a pas les permissions n??cessaires, on affiche une erreur
                if (!$this->user->isAdmin() && !$this->user->canInteractWithList($this->liste))
                    return $this->response->write($this->renderer->render(Renderer::REQUEST_AUTH));
                break;
            case 'POST':
                if (!$this->user->isAdmin() && !$this->user->canInteractWithList($this->liste) && empty($this->request->getParsedBodyParam('private_key')))
                    return $this->response->write($this->renderer->render(Renderer::REQUEST_AUTH));
                if (!$this->user->isAdmin() && !$this->user->canInteractWithList($this->liste) && !password_verify(filter_var($this->request->getParsedBodyParam('private_key') ?? "", FILTER_SANITIZE_STRING), $this->liste->private_key))
                    return $this->response->withRedirect($this->container->router->pathFor('lists_delete_id', ['id' => $this->liste->no], ["info" => "errtoken"]));
                break;
        }
        foreach ($this->liste->items as $item) {
            //Supression des reservations, des participations, des cagnottes et de l'image des items
            Reservation::where('item_id', 'LIKE', $item->id)->delete();
            Participation::whereCagnotteItemid($item->id)->delete();
            Cagnotte::where('item_id', 'LIKE', $item->id)->delete();
            if (file_exists($this->container['items_img_dir'] . DIRECTORY_SEPARATOR . $item->img))
                unlink($this->container['items_img_dir'] . DIRECTORY_SEPARATOR . $item->img);
            $item->delete();
        }
        //Supression des messages
        Message::where('list_id', 'LIKE', $this->liste->no)->delete();
        //Supression liste
        $this->liste->delete();
        return $this->response->withRedirect($this->container->router->pathFor('home', [], ["info" => "deleted"]));
    }

    /**
     * Control creation of a list
     * @return Response
     * @throws MethodNotAllowedException
     * @throws Exception
     */
    public function create(): Response
    {
        //Selon le type de requete, on affiche la page de cr??ation ou on cr??e la liste
        switch ($this->request->getMethod()) {
            case 'GET':
                return $this->response->write($this->renderer->render(Renderer::CREATE));
            case 'POST':
                //Si on a pas la propri??t?? obligatoire titre, on redirige vers la cr??ation
                $titre = filter_var($this->request->getParsedBodyParam('titre'), FILTER_SANITIZE_STRING);
                if (empty($titre))
                    return $this->response->withRedirect($this->container->router->pathFor('lists_create'));
                //On cree une liste, on force son num??ro a NULL pour ??viter d'avoir une liste fantome (id = -1)
                $liste = new Liste();
                $liste->no = NULL;
                $liste->titre = filter_var($this->request->getParsedBodyParam('titre'), FILTER_SANITIZE_STRING);
                $liste->description = filter_var($this->request->getParsedBodyParam('description'), FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? NULL;
                $liste->expiration = $this->request->getParsedBodyParam('expiration') !== "" ? filter_var($this->request->getParsedBodyParam('expiration'), FILTER_SANITIZE_STRING) : NULL;
                $liste->public_key = (!empty($this->request->getParsedBodyParam('public_key')) && trim($this->request->getParsedBodyParam('public_key')) !== "") ? filter_var($this->request->getParsedBodyParam('public_key'), FILTER_SANITIZE_STRING) : NULL;
                //Cr??ation du token de modification unique
                $token = (new TokenGenerator())->generateToken(16);
                $liste->private_key = password_hash($token, PASSWORD_DEFAULT);
                //Attribution de l'utilisateur associ??
                $email = filter_var($this->request->getParsedBodyParam('email'), FILTER_SANITIZE_EMAIL);
                $associated_user = User::where("mail", "LIKE", $email)->first();
                if (!empty($associated_user)) {
                    $liste->user_id = $associated_user->user_id;
                    $liste->published = 0;
                }
                $liste->save();
                $data = json_decode($_COOKIE['claimed_lists'], true);
                $data[] = $liste->no;
                setcookie('claimed_lists', json_encode($data), time() + (3600 * 480), "/", "");
                //Si l'utilisateur associ?? est null (email correspondant a aucun utilisateur inscrit), on cr??e un utilisateur temporaire qui sera verifi?? quand il s'inscrira
                if (empty($liste->user_id)) {
                    $tmp = new UserTemporaryResolver();
                    $tmp->list_id = $liste->no;
                    $tmp->email = $email;
                    $liste->update(['published' => 1]);
                    $tmp->save();
                }

                $path = $this->container->router->pathFor('lists_show_id', ["id" => $liste->no], ["public_key" => $liste->public_key]);
                return $this->response->write("<script type='text/javascript'>alert('{$this->container->lang['alert_modify_token']} $token');window.location.href='$path';</script>");
            default:
                throw new MethodNotAllowedException($this->request, $this->response, ['GET', 'POST']);
        }
    }

    /**
     * Control claim of a list
     * @return Response
     * @throws ForbiddenException
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    public function claim(): Response
    {
        //Si la liste n'existe pas, on declenche une erreur
        if (empty($this->liste))
            throw new NotFoundException($this->request, $this->response);
        //Si l'utilisateur n'existe pas ou n'est pas connect??, on d??clenche une erreur
        if (empty($_SESSION['LOGGED_IN']) || empty($this->user->username) || $this->liste->isClaimed())
            throw new ForbiddenException(message: $this->container->lang['exception_ressource_not_allowed']);
        switch ($this->request->getMethod()) {
            case 'GET':
                //Si l'utilisateur n'est pas admin, on demande l'authentification
                if (!$this->user->isAdmin())
                    return $this->response->write($this->renderer->render(Renderer::REQUEST_AUTH));
                break;
            case 'POST':
                /*Trois cas de figure :
                - L'utilisateur est admin, on revendique la liste
                - L'utilisateur est inconnu, et a saisi le token priv??
                - L'utilisateur est inconnu, et n'a pas saisi le token priv??, on lui affiche le formulaire d'authentification*/
                if (!$this->user->isAdmin() && empty($this->request->getParsedBodyParam('private_key')))
                    return $this->response->write($this->renderer->render(Renderer::REQUEST_AUTH));
                if (!$this->user->isAdmin() && !password_verify(filter_var($this->request->getParsedBodyParam('private_key') ?? "", FILTER_SANITIZE_STRING), $this->liste->private_key))
                    return $this->response->withRedirect($this->container->router->pathFor('lists_claim_id', ['id' => $this->liste->no], ["info" => "errtoken"]));
                break;
            default:
                throw new MethodNotAllowedException($this->request, $this->response, ['GET', 'POST']);
        }
        $this->liste->update([
            'user_id' => $this->user->user_id
        ]);
        $data = json_decode($_COOKIE['claimed_lists'], true);
        $data[] = $this->liste->no;
        setcookie('claimed_lists', json_encode($data), time() + (3600 * 480), "/", "");
        $tmp = UserTemporaryResolver::find($this->liste->no);
        if (!empty($tmp))
            $tmp->delete();
        return $this->response->withRedirect($this->container->router->pathFor('lists_show_id', ["id" => $this->liste->no], ["public_key" => $this->liste->public_key, "state" => "update"]));

    }

    /**
     * Control display of a list
     * @return Response
     * @throws ForbiddenException
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    public function show(): Response
    {
        //Si la liste n'existe pas, on declenche une erreur
        if (empty($this->liste))
            throw new NotFoundException($this->request, $this->response);
        //Si l'utilisateur est admin, on lui montre la liste
        if ($this->user->isAdmin())
            return $this->response->write($this->renderer->render(Renderer::SHOW, Renderer::ADMIN_MODE));
        /*On considere ici que l'utilisateur n'est pas admin et que la liste existe
        Si l'utilisateur est le propri??taire de la liste, on lui montre*/
        if ($this->user->canInteractWithList($this->liste))
            return $this->response->write($this->renderer->render(Renderer::SHOW, Renderer::OWNER_MODE));
        //A ce point, on considere que l'utilisateur est inconnu, on doit donc s'assurer qu'il precise le token de la liste si cette derniere en est pourvue
        if (!in_array($this->request->getMethod(), ['GET', 'POST']))
            throw new MethodNotAllowedException($this->request, $this->response, ['GET', 'POST']);
        //Si la liste n'est pas publi??e ou que la cl?? publique ne correspond pas, on declenche une erreur
        if (!$this->liste->isPublished() || (!$this->liste->isPublic() && !empty($this->liste->public_key) && $this->liste->public_key !== filter_var($this->request->getQueryParam('public_key', ""), FILTER_SANITIZE_STRING)))
            throw new ForbiddenException($this->container->lang['exception_forbidden'], $this->container->lang['exception_ressource_not_allowed']);
        //On affiche la liste
        return $this->response->write($this->renderer->render(Renderer::SHOW));
    }

}