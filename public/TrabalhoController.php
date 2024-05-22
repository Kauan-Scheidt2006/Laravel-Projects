<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use App\Models\Feira;
use App\Models\UnidadeEnsino;
use App\Models\Modalidade;
use App\Models\Categoria;
use App\Models\Trabalho;
use App\Models\Orientador;
use App\Models\Expositor;
use App\Models\TrabalhoOrientador;
use App\Models\TrabalhoExpositor;
use App\Models\GrauFormacao;
use App\Models\NivelEnsino;
use App\Models\User;
use App\Models\SituacaoTrabalho;
use App\Jobs\SendEmailJob;


use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class TrabalhoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $filtro = request()->input('filtro');
        $trabalhos = Trabalho::where('titulo', 'LIKE', $filtro.'%')->sortable()->paginate(12);

        if (request()->session()->has('toast')) {
            return view('trabalho.index')->with('trabalhos', $trabalhos)->with('filtro', $filtro)->with(session('toast'));
        }

        return view('trabalho.index')->with('trabalhos', $trabalhos)->with('filtro', $filtro);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $feiras = Feira::all();
        $unidadesEnsino = UnidadeEnsino::all();
        $modalidades = Modalidade::all();
        $categorias = Categoria::all();
        $situacoes = SituacaoTrabalho::all();
        return view('trabalho.create', ['feiras'=>$feiras , 'unidadesEnsino'=>$unidadesEnsino, 'modalidades'=>$modalidades, 'categorias'=>$categorias, 'situacoes'=>$situacoes]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $trabalho = new Trabalho();
        $feira = Feira::find($request->input('feira_id'));
        $categoria = Categoria::find($request->input('categoria_id'));

        $trabalho->titulo = $request->input('titulo');
        $trabalho->resumo = $request->input('resumo');
        $trabalho->feira_id = $request->input('feira_id');
        $trabalho->categoria_id = $request->input('categoria_id');
        $trabalho->unidade_ensino_id = $request->input('unidadeEnsino_id');
        $trabalho->modalidade_id = $request->input('modalidade_id');
        $trabalho->homologacaoObs = $request->input('homologacaoObs');
        $trabalho->pontoDeEnergia = $request->has('pontoDeEnergia');
        $trabalho->situacao_trabalho_id = $request->input('situacaoTrabalho');
        $trabalho->observacao = $request->input('observacao');

        //Lógica de salvar arquivo

        $anexo = $request->file('anexo');
        $feiraPath = 'f' . $feira->id;
        $categoriaPath = preg_replace('/[ -]+/' , '-' , $categoria->descricao);
        $directory = $feiraPath . DIRECTORY_SEPARATOR . $categoriaPath;
        
        $anexoPath = $anexo->storeAs($directory, $feiraPath.$request->input('titulo').'.'.$anexo->getClientOriginalExtension());
        
        $trabalho->anexo = $anexoPath;
        $trabalho->save();

        if(Auth::user()->tipoUsuario_id == 2){
            return redirect()->route('trabalhoResp.editResp',['id'=>$trabalho->id, 'idfeira'=>$feira->id])->with('toast', ['type' => 'success', 'message' => 'Trabalho adicionada com sucesso.']);
        }

        return redirect()->route('trabalho.edit', $trabalho->id)->with('toast', ['type' => 'success', 'message' => 'Trabalho adicionada com sucesso.']);
    }

    /**
     * Display the specified resource.
     */
    public function show(String $id)
    {
        $trabalho = Trabalho::find($id);

        return view('trabalho.show', ['trabalho'=>$trabalho]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(String $id)
    {
        $trabalho = Trabalho::find($id);

        $feiras = Feira::all();
        $unidadesEnsino = UnidadeEnsino::all();
        $modalidades = Modalidade::all();
        $categorias = Categoria::all();
        $orientadores = Orientador::all();
        $expositores = Expositor::all();
        $grausFormacao = GrauFormacao::all();
        $niveisEnsino = NivelEnsino::all();
        $situacoes = SituacaoTrabalho::all();

        return view('trabalho.edit', ['feiras'=>$feiras , 'unidadesEnsino'=>$unidadesEnsino, 'modalidades'=>$modalidades, 'categorias'=>$categorias, 'trabalho'=>$trabalho, 'orientadores'=>$orientadores, 'grausFormacao'=>$grausFormacao, 'expositores'=>$expositores, 'niveisEnsino'=>$niveisEnsino, 'situacoes'=>$situacoes]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $trabalho = Trabalho::find($id);
        $feira = Feira::find($request->input('feira_id'));
        $categoria = Categoria::find($request->input('categoria_id'));
        $situacaoAntiga = $trabalho->situacao_trabalho_id;

        $trabalho->titulo = $request->input('titulo');
        $trabalho->resumo = $request->input('resumo');
        $trabalho->feira_id = $request->input('feira_id');
        $trabalho->categoria_id = $request->input('categoria_id');
        $trabalho->unidade_ensino_id = $request->input('unidadeEnsino_id');
        $trabalho->modalidade_id = $request->input('modalidade_id');
        $trabalho->homologacaoObs = $request->input('homologacaoObs');
        $trabalho->pontoDeEnergia = $request->has('pontoDeEnergia');
        $trabalho->situacao_trabalho_id = $request->input('situacaoTrabalho');
        $trabalho->observacao = $request->input('observacao');
        //Lógica de salvar arquivo
        $anexo = $request->file('anexo');

        if($request->has('anexo')){
            $feiraPath = 'f' . $feira->id;
            $categoriaPath = preg_replace('/[ -]+/' , '-' , $categoria->descricao);
            $directory = $feiraPath . DIRECTORY_SEPARATOR . $categoriaPath;
            
            $anexoPath = $anexo->storeAs($directory, $feiraPath.$request->input('titulo').'.'.$anexo->getClientOriginalExtension());
            
            Storage::delete($trabalho->anexo);

            $trabalho->anexo = $anexoPath;
        }
        $trabalho->save();

        if($trabalho->situacao_trabalho_id == 2 && $situacaoAntiga != 2){
            $user = User::find($trabalho->unidadeEnsino->user_id);
            $conteudo = [$trabalho , $feira];
            //SendEmailJob::dispatch($user, 'homologacao', $conteudo);
            return redirect()->route('enviar-email-homologacao',['idtrabalho'=>$trabalho->id, 'idfeira'=>$feira->id]);
        }

        if(Auth::user()->tipoUsuario_id == 2){
            return redirect()->route('trabalhoResp.editResp',['id'=>$trabalho->id, 'idfeira'=>$feira->id])->with('toast', ['type' => 'success', 'message' => 'Trabalho Editado com sucesso.']);
        }

        return redirect()->route('trabalho.index')->with('toast', ['type' => 'success', 'message' => 'Trabalho editado com sucesso.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(String $id)
    {
        $trabalho = Trabalho::find($id);
        $feiras = Feira::All();

        try {
            $trabalho->delete();
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Database\QueryException && $e->errorInfo[1] == 1451) {
                return redirect()->route('trabalho.index')->with('toast', ['type' => 'warning', 'message' => 'Não é Possível excluir itens com vínculos']);
            } else {
                return redirect()->route('trabalho.index')->with('toast', ['type' => 'danger', 'message' => 'Erro Inesperado ('.$e->getMessage().")"]);
                echo "Erro ao excluir item: " . $e->getMessage();
            }
        }
        if(Auth::user()->tipoUsuario_id == 2){
            return view('responsavelGeral')->with('feiras', $feiras)->with('toast', ['type' => 'success', 'message' => 'Trabalho excluido com sucesso.']);
        }
        return redirect()->route('trabalho.index')->with('toast', ['type' => 'success', 'message' => 'Trabalho excluída com sucesso.']);
    }

    public function addOrientador(Request $request)
    {
        if(isset($request->busca)){
            $orientador = Orientador::find($request->input('id'));

            $orientador->nome = $request->input('nome');
            $orientador->nascimento = $request->input('nascimento');
            $orientador->naturalidade = $request->input('naturalidade');
            $orientador->rg = $request->input('rg');
            $orientador->telefone = $request->input('telefone');
            $orientador->email = $request->input('email');
            $orientador->graduacao = $request->input('graduacao');
            $orientador->grau_formacao_id = $request->input('grauFormacao');
    
            $orientador->save();
            try {
                $to = new TrabalhoOrientador();

                $to->trabalho_id = $request->input('trabalho_id');
                $to->orientador_id = $request->input('id');

                $to->save();
            } catch (\Exception $e) {
                if ($e instanceof \Illuminate\Database\QueryException && $e->errorInfo[1] == 1062) {
                    return redirect("trabalho/$request->trabalho_id/edit")->with('toast', ['type' => 'warning', 'message' => 'Orientador duplicado. Exclua para adicionar um novo.']);
                } else {
                    return redirect("trabalho/$request->trabalho_id/edit")->with('toast', ['type' => 'warning', 'message' => 'Erro inesperado: ' . $e->getMessage()]);
                }
            }
        }else{
            $orientador = new Orientador();
        
            $orientador->nome = $request->nome;
            $orientador->nascimento = $request->nascimento;
            $orientador->naturalidade = $request->naturalidade;
            $orientador->rg = $request->rg;
            $orientador->telefone = $request->telefone;
            $orientador->email = $request->email;
            $orientador->graduacao = $request->graduacao;
            $orientador->grau_formacao_id = $request->grauFormacao;
        
            $orientador->save();

            try {
                $to = new TrabalhoOrientador();

                $to->trabalho_id = $request->input('trabalho_id');
                $to->orientador_id = $orientador->id;

                $to->save();
            } catch (\Exception $e) {
                if ($e instanceof \Illuminate\Database\QueryException && $e->errorInfo[1] == 1062) {
                    return redirect("trabalho/$request->trabalho_id/edit")->with('toast', ['type' => 'warning', 'message' => 'Orientador duplicado. Exclua para adicionar um novo.']);
                } else {
                    return redirect("trabalho/$request->trabalho_id/edit")->with('toast', ['type' => 'warning', 'message' => 'Erro inesperado: ' . $e->getMessage()]);
                }
            }
        }

        return redirect("trabalho/$to->trabalho_id/edit")->with('toast', ['type' => 'success', 'message' => 'Orientador adicionado com sucesso.']);
    }

    public function delOrientador(Request $request)
    {
        $trabalho = Trabalho::find($request->trabalho_id);

        $trabalho->orientadores()->detach($request->orientador_id);

        return redirect("trabalho/$request->trabalho_id/edit")->with('toast', ['type' => 'success', 'message' => 'Orientador Removido com sucesso.']);
    }

    public function addExpositor(Request $request)
    {
        if(isset($request->buscaExp)){
            $expositor = Expositor::find($request->input('idExp'));

            $expositor->nome = $request->input('nomeExp');
            $expositor->nascimento = $request->input('nascimentoExp');
            $expositor->naturalidade = $request->input('naturalidadeExp');
            $expositor->rg = $request->input('rgExp');
            $expositor->telefone = $request->input('telefoneExp');
            $expositor->email = $request->input('emailExp');
            $expositor->nivel_ensino_id = $request->input('nivelEnsino');
    
            $expositor->save();
            try {
                $te = new TrabalhoExpositor();

                $te->trabalho_id = $request->input('trabalho_id');
                $te->expositor_id = $request->input('idExp');

                $te->save();
            } catch (\Exception $e) {
                if ($e instanceof \Illuminate\Database\QueryException && $e->errorInfo[1] == 1062) {
                    return redirect("trabalho/$request->trabalho_id/edit")->with('toast', ['type' => 'warning', 'message' => 'Expositor duplicado. Exclua para adicionar um novo.']);
                } else {
                    return redirect("trabalho/$request->trabalho_id/edit")->with('toast', ['type' => 'warning', 'message' => 'Erro inesperado: ' . $e->getMessage()]);
                }
            }
        }else{
            $expositor = new Expositor();
        
            $expositor->nome = $request->input('nomeExp');
            $expositor->nascimento = $request->input('nascimentoExp');
            $expositor->naturalidade = $request->input('naturalidadeExp');
            $expositor->rg = $request->input('rgExp');
            $expositor->telefone = $request->input('telefoneExp');
            $expositor->email = $request->input('emailExp');
            $expositor->nivel_ensino_id = $request->input('nivelEnsino');
        
            $expositor->save();

            try {
                $te = new TrabalhoExpositor();

                $te->trabalho_id = $request->input('trabalho_id');
                $te->expositor_id = $expositor->id;

                $te->save();
            } catch (\Exception $e) {
                if ($e instanceof \Illuminate\Database\QueryException && $e->errorInfo[1] == 1062) {
                    return redirect("trabalho/$request->trabalho_id/edit")->with('toast', ['type' => 'warning', 'message' => 'Expositor duplicado. Exclua para adicionar um novo.']);
                } else {
                    return redirect("trabalho/$request->trabalho_id/edit")->with('toast', ['type' => 'warning', 'message' => 'Erro inesperado: ' . $e->getMessage()]);
                }
            }
        }

        return redirect("trabalho/$te->trabalho_id/edit")->with('toast', ['type' => 'success', 'message' => 'Expositor adicionado com sucesso.']);
    }

    public function delExpositor(Request $request)
    {
        $trabalho = Trabalho::find($request->trabalho_id);

        $trabalho->expositores()->detach($request->expositor_id);

        return redirect("trabalho/$request->trabalho_id/edit")->with('toast', ['type' => 'success', 'message' => 'Expositor Removido com sucesso.']);
    }
}
