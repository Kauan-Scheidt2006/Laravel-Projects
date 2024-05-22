<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use App\Models\File;
use Illuminate\Http\Request;

class FileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Buscar o arquivo no banco de dados
        $arquivos = File::orderBy("created_at", "DESC")->get();

        // Obter o URL do arquivo

        return view("filesSaved.index", ['arquivos'=>$arquivos]);   
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("filesSaved.create");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
 /*
   $file=$request->file('file');
   $filename = $file->getClientOriginalName();
    $fileextension = $file->getClientOriginalExtension();
   $file->move(public_path(), $filename);
    */
    /*
    $f = new File();
    $f->save();
    
    $file=$request->file('file');
    $dir = preg_replace('/[ -]+/', "-", $request->input('titulo'));
    
    $dir = "diretorio".DIRECTORY_SEPARATOR;
    $file->storeAs($dir, "teste".$f->id.".".$file->getClientOriginalExtension());*/
    $file = $request->file("file");
    
    //$caminho = preg_replace('/[ -*+¨!(@\/#)$%]+/', '-', $file->getClientOriginalName());
    $caminho = uniqid("teste").".".$file->getClientOriginalExtension();
    $titulo = $request->input("titulo");
    
    $path = storage_path("app/uploads/");
    $file->move($path, $caminho);
    

    $arquivo = new File();
    $arquivo->titulo = $titulo;
    $arquivo->caminho = $caminho;
    $arquivo->save();

    return redirect()->route("FilesSaved.index");

    
    }

    /**
     * Display the specified resource.
     */
    public function show(File $file)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(File $file)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, File $file)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $file = File::find($id);

        Storage::delete("uploads/".$file->caminho);
        File::destroy($file->id);

        return redirect()->route("FilesSaved.index");   
    }

    public function download(Request $request){
        $file = File::find($request->file_id);

        Storage::download("uploads/".$file->caminho);

        return redirect()->route("FilesSaved.index");

    }
}
