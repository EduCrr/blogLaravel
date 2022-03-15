<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Category;
use App\Models\PostCategory;
use App\Models\Post;
use App\Models\PostPhoto;

class CategoryController extends Controller
{
    private $loggedUser;
    public function __construct()
    {
        $this->middleware('auth:api', [ 'except' => [ 'read', 'all' ] ]);
        $this->loggedUser = auth()->user();  //info user
    }

    public function create(Request $request){
        $array = ['error' => ''];

        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        $name = $request->input('name');

        if(!$validator->fails()){
            $catExists = Category::where('name', $name)->count();

            if($catExists === 0){
                
                $newCat = new Category();
                $newCat->name = $name;
                $str = strtolower($name);
                $newCat->slug = preg_replace('/\s+/', '-', $str);
                $newCat->save();

                $array['success'] = 'Categoria criada com sucesso!';


            }else{
                $array['error'] = 'Categoria já existe!';
                return $array;
            }


        }else{
            $array['error'] = 'Preencha corretamente!';
            return $array;
        }

        return $array;
    }

    public function read($id){
        $array = ['error' => ''];

        $cat = Category::select(['name'])->where('id', $id)->first();
        $array['category'] = $cat->name;

        $getCats = PostCategory::select()->where('id_categorie', $id)->get();
        
        foreach($getCats as $key => $item){
            $posts = Post::select()->where('id', $item['id_post'])->first();
            $array['posts'][$key] = $posts;

            $photo = PostPhoto::select(['url'])->where('id_post', $item['id_post'])->first();
            $array['posts'][$key]['photo'] = $photo;

        }

        $array['path'] = url('media/uploads/');
        
        return $array;
    }

    public function all(){
        $array = ['error' => ''];


        $cats = Category::all();

        if($cats){
            $array['categories'] = $cats;
        }else{
            $array['error'] = 'Nenhuma categoria existente!';
            return $array;
        }

        return $array;
    }
}
