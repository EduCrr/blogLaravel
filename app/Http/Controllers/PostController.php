<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Post;
use App\Models\User;
use App\Models\PostPhoto;
use App\Models\PostCategory;
use App\Models\Category;
use App\Models\UserFavorite;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\File;

class PostController extends Controller
{
    private $loggedUser;
    public function __construct()
    {
        $this->middleware('auth:api', [ 'except' => [ 'single', 'list', 'search' ] ] );
        $this->loggedUser = auth()->user();  //info user
    }

    public function create(Request $request){
        $array = ['error' => ''];

        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'content' => 'required',
            'category' => 'required',
            'images.*' =>  'required|image|mimes:jpeg,png,jpg,svg',
        ]);

        if(!$validator->fails()){

            $title = $request->input('title');
            $content = $request->input('content');
            $category = $request->input('category');
            $images = $request->file('images.*');

            $titleExists = Post::where('title', $title)->count();

            if($titleExists === 0){

                $newPost = new Post();
                $newPost->title = $title;
                $newPost->id_user = $this->loggedUser->id;
                $newPost->content = $content;
                $newPost->created_at = date('Y-m-d H:i:s');
                $str = strtolower($title);
                $newPost->slug = preg_replace('/\s+/', '-', $str);

                $newPost->save();

                //categoria
                $newPostCat = new PostCategory();
                $newPostCat->id_categorie = $category;
                $newPostCat->id_post = $newPost->id;
                $newPostCat->save();

                //images
                if($images){
                    foreach($images as $item){
                        
                        $dest = public_path('media/uploads');
                        $photoName = md5(time().rand(0,9999)).'.jpg';
                
                        $img = Image::make($item->getRealPath());
                        $img->save($dest.'/'.$photoName);

                        $newPostPhoto = new PostPhoto();
                        $newPostPhoto->id_post = $newPost->id;
                        $newPostPhoto->url = $photoName;
                        $newPostPhoto->save();
                    }
                }

            }else{
                $array['error'] = 'Esse título já existe';
                return $array;
            }


        }else{
            $array['error'] = $validator->errors()->first();
            return $array;
        }

        return $array;

    }

    public function list(Request $request){
        $array = ['error' => ''];

        $offset = $request->input('offset');
        if(!$offset){
            $offset = 0;
        }

        $posts = Post::select()->skip($offset)->take(10)->get(); 

        if($posts){
            foreach($posts as $key => $item){
                $photo = PostPhoto::select(['id', 'url'])->where('id_post', $item['id'])->get();
                //$photo[$key]['url'] = url('media/uploads/'.$photo[$key]['url']);
                
                $posts[$key]['photos'] = $photo;

                $catPost = PostCategory::where('id_post', $item['id'])->first();
                $category = Category::where('id', $catPost->id_categorie)->first();
                $posts[$key]['category'] = $category;

            }   

            $array['posts'] = $posts;
            $array['path'] = url('media/uploads/');

        }else{
            $array['error'] = 'Posts não encontrados!';
            return $array;
        }
        
        return $array;
    }

    public function single($id){
        $array = ['error' => ''];

        $post = Post::findOrFail($id);

        if($post){

            $post['photos'] = [];
            $post['category'] = [];
            $post['user'] = [];
           

            //fotos
            $post['photos'] = PostPhoto::select(['id', 'url'])->where('id_post', $id)->get();
            foreach($post['photos'] as $key => $item){
                $post['photos'][$key]['url'] = url('media/uploads/'.$post['photos'][$key]['url']);
            }

            //categoria
            $cat = PostCategory::where('id_post', $id)->first();
            $post['category'] = Category::where('id', $cat->id_categorie)->first();
            $array['post'] = $post;

            //user
            $user = User::select(['id', 'name', 'avatar'])->where('id', $post->id_user)->first();
            $user['avatar'] = url('media/avatars/'.$user['avatar']);
            $post['user'] = $user;

        }else{
            $array['error'] = 'Post não encontrado!';
            return $array;
        }

        return $array;
    }

    public function myPosts(){
        $array = ['error' => ''];

        $myPost = Post::select(['id', 'title', 'slug', 'created_at'])->where('id_user', $this->loggedUser->id)->get();

        //fotos
        if($myPost){
            foreach($myPost as $key => $item){
                $photo = PostPhoto::select(['id', 'url'])->where('id_post', $item['id'])->first();
                $myPost[$key]['photos'] = $photo;
            }

        }else{
            $array['error'] = 'Posts não encontrados!';
            return $array;
        }
        

        $array['post'] = $myPost;
        $array['path'] = url('media/uploads/');
        return $array;
    }

    public function search(Request $request){
        $array = ['error' => ''];

        $q = $request->input('q');
        
        if($q){

            $posts = Post::where('title', 'LIKE', '%'.$q.'%')->get();

            foreach($posts as $key => $item){ 
                
                $photo = PostPhoto::select(['id', 'url'])->where('id_post', $item['id'])->first();
                //$photo[$key]['url'] = url('media/uploads/'.$photo[$key]['url']);
                
                $posts[$key]['photo'] = $photo;
            }

            $array['posts'] = $posts->values();
            

        }else{
            $array['error'] = 'Digite algo para buscar!';
            return $array;
        }
        $array['path'] = url('media/uploads/');
        return $array;

    }

    public function delete($id){
        $array = ['error' => ''];

        $post = Post::find($id);

        if($id){

            //deletar categorias
            $catDel = PostCategory::where('id_post', $post->id)->first();
            $catDel->delete();

            //deletar images banco e pasta
            $imgDel = PostPhoto::where('id_post', $post->id)->get();
            foreach($imgDel as $item){
                File::delete(public_path("/media/uploads/".$item["url"]));
                $item->delete();
            }

            //deletar post banco
            $post->delete();

        }

        return $array;  
    }

    public function update(Request $request, $id){
        $array = ['error' => ''];

        $rules = [
            'title' => 'min:2',
            'content' => 'min:2',

        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()){
            $array['error'] = $validator->errors()->first();
            return $array;
        } 


        $title = $request->input('title');
        $content = $request->input('content');
        $category = $request->input('category');
        $post = Post::find($id);


            

            if($title){
                $post->title = $title;
            }

            if($content){
                $post->content = $content;
            }

            $cat = Category::find($category);
            
            if($cat){
                $newPostCat = PostCategory::where('id_post', $id)->first();
                $newPostCat->id_categorie = $category;
                $newPostCat->save();
                
            }else{
                $array['error'] = 'Categoria não existe';
                return $array;
            }
            
            $post->save();
        

        return $array;


    }

    public function deleteImage($id){
        $array = ['error' => ''];

        $imageDel = PostPhoto::find($id);

        if($imageDel){
            File::delete(public_path("/media/uploads/".$imageDel->url));
            $imageDel->delete();

        }else{
            $array['error'] = 'Imagem não existe';
            return $array;
        }

        return $array;

    }

    public function updateImages(Request $request, $id){
        $array = ['error' => ''];

        $validator = Validator::make($request->all(), [
            'images.*' =>  'required|image|mimes:jpeg,png,jpg,svg',
        ]);

        if(!$validator->fails()){

            $images = $request->file('images.*');
                $post = Post::find($id);
                //images
                if($images){
                    foreach($images as $item){
                        
                        $dest = public_path('media/uploads');
                        $photoName = md5(time().rand(0,9999)).'.jpg';
                
                        $img = Image::make($item->getRealPath());
                        $img->save($dest.'/'.$photoName);

                        $newPostPhoto = new PostPhoto();
                        $newPostPhoto->id_post = $post->id;
                        $newPostPhoto->url = $photoName;
                        $newPostPhoto->save();
                    }
                }


        }else{
            $array['error'] = $validator->errors()->first();
            return $array;
        }

        return $array;

    }
}
