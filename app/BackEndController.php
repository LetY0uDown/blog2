<?php
declare(strict_types=1);


namespace App;


use App\Core\Auth;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Scrawler\Arca\Database;
use SimpleValidator\Validator;
use EdSDK\FlmngrServer\FlmngrServer;
use EasySlugger\Slugger;

class BackEndController
{
use Auth;

    private BackEndView $View;
    private Database $Model;

    public function __construct(Database $Model, BackEndView $View)
    {
        $this->View = $View;
        $this->Model = $Model;
    }

    public function responseWrapper(string $str) : ResponseInterface
    {
        $response = new Response;
        $response->getBody()->write($str);
        return $response;
    }

    public function goUrl(string $url)
    {
        return new RedirectResponse($url);
    }

    public function index(ServerRequestInterface $request) : ResponseInterface
    {
        return $this->showDashboard($request);
    }

    /**
     * Эти методы относится к модели - нужно определиться выносить модели в отдельный класс
     * или оставлять в этом...
     */
    public function getUserByEmail(string $email)
    {
        $users = $this->Model->find('users')
            ->where('email = :email')
            ->setParameter('email', $email)
            ->first();

        return $users->toArray();
    }

    public function getAll(string $tablename) : array
    {
        $all = $this->Model->get($tablename);

        return $all->toArray();
    }

    public function getById(string $tablename, int $id)
    {
        $all = $this->Model->get($tablename, $id);

        return $all->toArray();
    }


    /**
    * end Model
     **/


    public function UserSignIn(ServerRequestInterface $request) : ResponseInterface
    {
        $requestBody = $request->getParsedBody();
        $user = $this->getUserByEmail($requestBody['email']);

        if (empty($user)) {
            return $this->responseWrapper('User not found...');
        } else {
            if ($requestBody['password'] == $user['password'])
            {
                //return $this->responseWrapper('Ok');
                $this->signIn($user['username']);
                return $this->goUrl('/admin');
            } else {
                $r = $this->responseWrapper('Неверный пароль');
                dd($r);
            }
        }
    }

    public function userLogOut(ServerRequestInterface $request): ResponseInterface
    {
        $this->signOut();
        return $this->goUrl('/admin');
    }

    public function UserSignUp(ServerRequestInterface $request): ResponseInterface
    {
        $requestBody = $request->getParsedBody();
        $rules = [[
                'required',
                'alpha',
                'min_length(5)',
                'max_length(50)'
            ],
            'email' => [
                'required',
                'email'
            ],
            'password' => [
                'required',
                'min_length(5)',
                'max_length(50)',
                'equals(:password_verify)'
            ],
            'password_verify' => [
                'required'
            ]
        ];

        $validation_result = Validator::validate($requestBody, $rules);

        if ($validation_result->isSuccess() == true) {
            //dd("validation ok");
            $user = $this->getUserByEmail($requestBody['email']);
            if (empty($user)) {
                $user = $this->Model->create('users');
                $user->username = $requestBody['username'];
                $user->email = $requestBody['email'];
                $user->password = password_hash($requestBody['password'], PASSWORD_DEFAULT);
                $user->save();
                return $this->responseWrapper('User sign up is OK!');
            } else {
                return $this->responseWrapper('Email is used ;(');
            }
        } else {
            echo "validation not ok";
            dd($validation_result->getErrors());
        }
    }

    public function filemanager(ServerRequestInterface $request): ResponseInterface
    {

        //dd(__DIR__ . '../files');
        $html =  FlmngrServer::flmngrRequest([
            'dirFiles' => __DIR__ . '/files/',
        ]);
        var_dump($html);
        //$html = $this->View->index();
        //return $this->responseWrapper($html);
    }

    public function showDashboard(ServerRequestInterface $request) : ResponseInterface
    {
        $html = $this->View->index();
        return $this->responseWrapper($html);
    }

    public function showSignInForm(ServerRequestInterface $request) : ResponseInterface
    {
        $html =$this->View->showSignInForm();
        return $this->responseWrapper($html);
    }

    public function showSignUpForm(ServerRequestInterface $request) : ResponseInterface
    {
        $html = $this->View->showSignUpForm();
        return $this->responseWrapper($html);
    }

    public function showUsersList(ServerRequestInterface $request) : ResponseInterface
    {
        $users = $this->getAll('users');
        //dd($this->Model->manager->listTableColumns('users'));</p> <p>
        $columns = ['username','email'];
        $html = $this->View->showUserList($users);

        return $this->responseWrapper($html);
    }

    public function showArticlesList(ServerRequestInterface $request) : ResponseInterface
    {
        $articles = $this->getAll('articles');
        $categories = $this->getAll('categories');
        $html = $this->View->showArticlesList($articles, $categories);

        return $this->responseWrapper($html);
    }

    public  function showTagsList(ServerRequestInterface $request) : ResponseInterface
    {
        $tags = $this->getAll('tags');

        $html = $this->View->showTagsList($tags);

        return  $this->responseWrapper($html);
    }

    public function showAddArticleForm(ServerRequestInterface $request) : ResponseInterface
    {
        $categories = $this->getAll('categories');
        $html = $this->View->showAddArticleForm(['id' => 0], $categories);

        return $this->responseWrapper($html);
    }

    private function getArticle($id) : mixed
    {
        if ($id == 0)
            $article = $this->Model->create('articles');
        else
            $article = $this->Model->get('articles', $id);

        return $article;
    }

    public function AddArticle(ServerRequestInterface $request) : ResponseInterface
    {
        $requestBody = $request->getParsedBody();
        $id = $requestBody['id'];
        $article = $this->getArticle($id);

        $article->title = $requestBody['title'];
        $article->category_id = $requestBody['category'];
        $article->intro_image = $requestBody['intro_image'];
        $article->intro_text = $requestBody['intro_text'];
        $article->content = $requestBody['content'];

        $article->save();

        return  $this->goUrl('/admin/articles');
    }

    public function showAddTagForm(ServerRequestInterface $request) : ResponseInterface
    {
        $tags = $this->getAll('tags');
        $html = $this->View->showAddTagForm($tags);

        return $this->responseWrapper($html);
    }

    public function AddTag(ServerRequestInterface $request) : ResponseInterface
    {
        $requestBody = $request->getParsedBody();

        $tag = $this->Model->create('tags');

        $tag->title = $requestBody['title'];

        $tag->save();

        return  $this->goUrl('/admin/tags');
    }

    public function showCategoriesList(ServerRequestInterface $request) : ResponseInterface
    {
        $categories = $this->getAll('categories');
        $html = $this->View->showCategoriesList($categories);

        return  $this->responseWrapper($html);
    }

    public function showAddCategoryForm(ServerRequestInterface $request) : ResponseInterface
    {
        $categories = $this->getAll('categories');
        $html = $this->View->showAddCategoryForm($categories);

        return $this->responseWrapper($html);
    }

    public function AddCategory(ServerRequestInterface $request) : ResponseInterface
    {
        $requestBody = $request->getParsedBody();

        $category = $this->Model->create('categories');

        $category->title = $requestBody['title'];

        $category->save();

        return $this->goUrl('/admin/categories');
    }

    public function showEditArticleForm(ServerRequestInterface $request, array $args) : ResponseInterface
    {
        $article = $this->Model->get('articles', $args['id']);
        $categories = $this->getAll('categories');

        $html = $this->View->showAddArticleForm($article, $categories);

        return $this->responseWrapper($html);
    }

    public function deleteArticle(ServerRequestInterface $request, array $args) : ResponseInterface
    {
        $article = $this->Model->get('articles', $args['id']);

        $this->Model->delete($article);

        return  $this->goUrl('/admin/articles');
    }

    public function deleteCategory(ServerRequestInterface $request, array $args) : ResponseInterface
    {
        $category = $this->Model->get('categories', $args['id']);

        $this->Model->delete($category);

        return  $this->goUrl('/admin/categories');
    }

    public function deleteTag(ServerRequestInterface $request, array $args) : ResponseInterface
    {
        $tag = $this->Model->get('tags', $args['id']);

        $this->Model->delete($tag);

        return  $this->goUrl('/admin/tags');
    }
}