<?php

use PortalPeru\Entities\Post;
use PortalPeru\Entities\PostPhoto;
use PortalPeru\Repositories\BaseRepo;
use PortalPeru\Repositories\CategoryRepo;
use PortalPeru\Repositories\PostOrderRepo;
use PortalPeru\Repositories\PostPhotoRepo;
use PortalPeru\Repositories\PostRepo;
use PortalPeru\Repositories\TagRepo;

class AdminPostsController extends \BaseController {

    protected $rules = [
        'titulo' => 'required',
        'descripcion' => 'required|min:10|max:255',
        'contenido' => 'required',
        'imagen' => 'mimes:jpeg,jpg,png',
        'categoria' => '',
        'orden' => '',
        'published_at' => 'required',
        'publicar' => 'required|in:1,0'
    ];

    protected $categoryRepo;
    protected $postRepo;
    protected $postOrder;
    protected $postPhotoRepo;
    protected $tagRepo;

    public function __construct(CategoryRepo $categoryRepo,
                                PostRepo $postRepo,
                                PostOrderRepo $postOrderRepo,
                                PostPhotoRepo $postPhotoRepo,
                                TagRepo $tagRepo)
    {
        $this->categoryRepo = $categoryRepo;
        $this->postRepo = $postRepo;
        $this->postOrderRepo = $postOrderRepo;
        $this->postPhotoRepo = $postPhotoRepo;
        $this->tagRepo = $tagRepo;
    }

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
    public function index()
    {
        $posts = $this->postRepo->search(Input::all(), BaseRepo::PAGINATE, 'published_at', 'desc');
        $category = $this->categoryRepo->lists('titulo', 'id');
        return View::make('admin.posts.list', compact('posts', 'category'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $category = $this->categoryRepo->all()->lists('titulo', 'id');
        $order = $this->postOrderRepo->all()->lists('titulo', 'id');
        $tags = $this->tagRepo->all()->lists('titulo', 'id');
        $selected = [];
        return View::make('admin.posts.create', compact('category', 'order', 'tags', 'selected'));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store()
    {
        $data = Input::all();

        $validator = Validator::make($data, $this->rules);

        if($validator->passes())
        {
            //CREAR CARPETA CON FECHA Y MOVER IMAGEN
            CrearCarpeta();
            $ruta = "upload/".FechaCarpeta();
            $ruta_fecha = FechaCarpeta();
            $archivo = Input::file('imagen');
            $file = FileMove($archivo,$ruta);

            //VARIABLES
            $titulo = Input::get('titulo');
            $video = Input::get('video');
            $categoria = Input::get('categoria');
            $orden = Input::get('orden');

            //TAGS
            $tags=Input::get('tags');
            if($tags==""){ $union_tags=0; }
            elseif($tags<>""){ $union_tags=implode(",", $tags);}

            //CONVERTIR TITULO A URL$union_tags
            $slug_url = \Str::slug($titulo);

            //GUARDAR DATOS
            $post = new Post($data);
            $post->slug_url = $slug_url;
            $post->video = $video;
            $post->category_id = $categoria;
            $post->post_order_id = $orden;
            $post->tags = '0,'.$union_tags.',0';
            $post->imagen = $file;
            $post->imagen_carpeta = $ruta_fecha;
            $post->user_id = Auth::user()->id;
            $this->postRepo->create($post, $data);

            //REDIRECCIONAR A PAGINA PARA VER DATOS
            return Redirect::route('administrador.posts.index');
        }
        else
        {
            return Redirect::back()->withInput()->withErrors($validator->messages());
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $post = $this->postRepo->findOrFail($id);

        $tags = $post->tags;
        $tags = explode(",", $tags);
        $tags = $this->tagRepo->findOrFail($tags);

        return View::make('admin.posts.show', compact('post', 'tags'));
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $post = $this->postRepo->findOrFail($id);
        $category = $this->categoryRepo->all()->lists('titulo', 'id');

        $tags = $this->tagRepo->all();
        $tags_select = $post->tags;
        $tags_select = explode(",", $tags_select);
        $tags_select = $this->tagRepo->findOrFail($tags_select);

        return View::make('admin.posts.edit', compact('post', 'category', 'tags', 'tags_select'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update($id)
    {
        $post = $this->postRepo->findOrFail($id);

        $data = Input::only(['titulo','descripcion','contenido','published_at','publicar']);

        $validator = Validator::make($data, $this->rules);

        if($validator->passes())
        {
            //VARIABLES
            $titulo = Input::get('titulo');
            $video = Input::get('video');
            $categoria = Input::get('categoria');

            //CONVERTIR TITULO A URL
            $slug_url = \Str::slug($titulo);

            //VERIFICAR SI SUBIO IMAGEN
            if(Input::hasFile('imagen')){
                CrearCarpeta();
                $ruta = "upload/".FechaCarpeta();
                $archivo = Input::file('imagen');
                $file = FileMove($archivo,$ruta);
                $imagen = $file;
                $imagen_carpeta = FechaCarpeta();
            }else{
                $imagen = Input::get('imagen_actual');
                $imagen_carpeta = Input::get('imagen_actual_carpeta');
            }

            //TAGS
            $tags=Input::get('tags');
            if($tags==""){ $union_tags=0; }
            elseif($tags<>""){ $union_tags=implode(",", $tags);}

            //GUARDAR DATOS
            $post->imagen = $imagen;
            $post->imagen_carpeta = $imagen_carpeta;
            $post->video = $video;
            $post->category_id = $categoria;
            $post->tags = '0,'.$union_tags.',0';
            $post->slug_url = $slug_url;
            $post->user_id = Auth::user()->id;
            $this->postRepo->update($post,$data);

            //REDIRECCIONAR A PAGINA PARA VER DATOS
            return Redirect::route('administrador.posts.index');
        }
        else
        {
            return Redirect::back()->withInput()->withErrors($validator->messages());
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }

    public function photosList($post)
    {
        $posts = $this->postRepo->findOrFail($post);
        $photos = $this->postPhotoRepo->where('post_id', $post)->get();
        return View::make('admin.posts-photos.list', compact('posts', 'photos'));
    }

    public function photosUpload($post)
    {
        $posts = $this->postRepo->findOrFail($post);
        return View::make('admin.posts-photos.upload', compact('posts'));
    }

    public function photosUploadSave($post)
    {
        //CREAR CARPETA CON FECHA Y MOVER IMAGEN
        CrearCarpeta();
        $ruta = "upload/".FechaCarpeta();
        $ruta_fecha = FechaCarpeta();
        $archivo = Input::file('file');
        $file = FileMove($archivo,$ruta);

        //GUARDAR DATOS
        $photo = new PostPhoto();
        $photo->imagen = $file;
        $photo->imagen_carpeta = $ruta_fecha;
        $photo->post_id = $post;
        $photo->user_id = \Auth::user()->id;
        $photo->save();
    }


}