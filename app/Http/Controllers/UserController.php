<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\UserFavorite;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\File;

class UserController extends Controller
{
    private $loggedUser;
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();  //info user
    }

    public function read(){
        $array = ['error' => ''];

        $infoUser = $this->loggedUser;
        $infoUser['avatar'] = url('media/avatars/'.$infoUser['avatar']);

        $array['user'] = $infoUser;

        return $array;
    }

    public function update(Request $request){
        $array = ['error' => ''];

        $rules = [
            'name' => 'min:2',
            'email' => 'email',
            'password' => 'min:1',
            'password_confirm' => 'min:1'
        ];

        $validator = Validator::make($request->all(), $rules);


        if($validator->fails()){
            $array['error'] = $validator->messages();
        }

        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');
        $password_confirm = $request->input('password_confirm');

        //info do user logado

        $user = User::find($this->loggedUser->id);

        if($name){
            $user->name = $name;
        }

        if($email){
            if($user->email != $email){
                $emailExist = User::where('email', $email)->count();

                if($emailExist === 0){
                    $user->email = $email;
                }else{
                    $array['error'] = 'Email já existe';
                    return $array;
                }
            }
        }

         if($password && $password_confirm){
            if($password === $password_confirm){
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $user->password = $hash;

            }else{
                $array['error'] = 'Senhas não batem!';
                return $array;
            }
        }

        $user->save();

        return $array;
    }

    public function updateAvatar(Request $request){
        $array = ['error' => ''];

        $rules = [
            'avatar' => 'required|image|mimes:jpg,png,jpeg',
        ];

        $validator = Validator::make($request->all(), $rules);
        
        if($validator->fails()){
            $array['error'] = $validator->messages();
            return $array;
        }

        $avatar = $request->file('avatar');

        $dest = public_path('media/avatars');
        $avatarName = md5(time().rand(0,9999)).'.jpg';

        $img = Image::make($avatar->getRealPath());
        $img->fit(300,300)->save($dest.'/'.$avatarName);

        $user = User::find($this->loggedUser->id);

        //excluir avatar antigo
        if ($user["avatar"] !== $avatarName) {
            File::delete(public_path("/media/avatars/".$user["avatar"]));
        }

        $user->avatar = $avatarName;

        $user->save();
        
        return $array;
    }

    public function favorites(){
        $array = ['error' => '', 'list' => []];

        $favs = UserFavorite::select()->where('id_user', $this->loggedUser->id)->get();

        if($favs){
            foreach($favs as $item){
                $posts = Post::find($item['id_post']);

                $photo = PostPhoto::select(['url'])->where('id_post', $posts->id)->first();
                $posts['photo'] = $photo;

                $array['list'][] = $posts;

            }
        }else{
            $array['error'] = "Você não tem favoritos!";
            return $array;
        }

        return $array;

    }

    public function toggleFavorites(Request $request){
        $array = ['error' => ''];

        $idPost = $request->input('id');

        $post = Post::find($idPost);

        if($post){
            $hasFav = UserFavorite::select()->where('id_user', $this->loggedUser->id)->where('id_post', $post->id)->first();
            if($hasFav){
                $hasFav->delete();
                $array['have'] = false;
            }else{
                $newFav = New UserFavorite();
                $newFav->id_user = $this->loggedUser->id;
                $newFav->id_post = $post->id;
                $newFav->save();
                
                $array['have'] = true;

            }

        }else{
            $array['error'] = "Precisa add um post!";
            return $array;
        }

        return $array;

    }
}
