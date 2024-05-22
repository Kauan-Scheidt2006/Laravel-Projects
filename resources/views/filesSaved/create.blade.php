@extends("filesSaved.base")

@section("body")
<h1 style="text-align: center">Anexar Arquivo ao Gerenciador</h1>
<br>
<a href="{{route("FilesSaved.index")}}" class="btn btn-warning">Ver Lista de arquivos</a>
<br><br>
<form action="{{route("FilesSaved.store")}}" method="post" class="form-control" enctype="multipart/form-data">
    @csrf
        <div class="col-4">
        </div>
        <div style="text-align: center">
            <label for="titulo">Título do Arquivo</label><br>
            <input type="text" name="titulo" id="titulo" required class="form-control">        
            <br><br><br>
            <label for="file">Adicione o Arquivo</label> <br>
            <input type="file" name="file" id="file" required>       
            <br><br>
            <input type="submit" value="Enviar" class="btn btn-success">    
        
        </div>
    
</form>
@endsection