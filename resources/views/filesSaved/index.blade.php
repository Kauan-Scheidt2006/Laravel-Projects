@extends("filesSaved.base")

@section("body")

<h1 style="text-align: center">Gerenciador de Arquivos</h1>
<table class="table" style="text-align: center">
    <tr>
        <th>Id</th> <th>Titulação do Arquivo</th> <th>Nomeação Codificada do Arquivo</th> 
        <th>Download</th> <th>Destroy</th>
    </tr>

    @foreach($arquivos as $arquivo)
        <tr>
            <td>{{$arquivo->id}}</td>  <td>{{$arquivo->titulo}}</td>  <td>{{$arquivo->caminho}}</td>
            <td>
                <form action="{{route("download")}}" method="post">
                    @csrf
                    <input type="hidden" name="file_id" value="{{$arquivo->id}}">
                    <input type="submit" value="Baixar Arquivo" class="btn btn-secondary">
                </form>
            </td>

            <td>
                <form action="{{route("FilesSaved.destroy", $arquivo->id)}}" method="post">
                    @method("DELETE")
                    @csrf 
                    <input type="submit" value="Deletar Arquivo" class="btn btn-danger">
                </form>
            </td>
        </tr>
    @endforeach
</table>



@endsection