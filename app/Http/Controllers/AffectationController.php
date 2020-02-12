<?php

namespace App\Http\Controllers;

use App\Affectation;
use Illuminate\Http\Request;

use App\Traits\AffectationTrait;
use App\TypeMouvement;
use App\Article;
use App\TypeAffectation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

use PDF;


class AffectationController extends Controller
{
    use AffectationTrait;

    function __construct()
    {
         $this->middleware('permission:affectation-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $recherche_cols = ['id', 'objet'];
        $recherche_cols_val = ['id' => 'id', 'objet' => 'objet'];

        $sortBy = 'id';
        $orderBy = 'asc';
        $perPage = 5;
        $q = null;
        if ($request->has('orderBy')) $orderBy = $request->query('orderBy');
        if ($request->has('sortBy')) $sortBy = $recherche_cols_val[$request->query('sortBy')];
        if ($request->has('perPage')) $perPage = $request->query('perPage');
        if ($request->has('q')) $q = $request->query('q');
        $affectations = Affectation::search($q)->where('tags', 'NOT LIKE', "%Systeme%")->OrWhereNull('tags')->orderBy($sortBy, $orderBy)->paginate($perPage);
        return view('affectations.index', compact('affectations', 'recherche_cols', 'orderBy', 'sortBy', 'q', 'perPage'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Affectation  $affectation
     * @return \Illuminate\Http\Response
     */
    public function show(Affectation $affectation)
    {
        //dd($affectation);
        $affectation = Affectation::with(['statut','affectationarticles'])->where('id', $affectation->id)->first();

        //dd('relations', $affectation->relationships(), 'sub children', $affectation->subChildrenRelations());
        $elem_arr = $this->getElemArr($affectation->typeAffectation->tags, $affectation->beneficiaire->id);

        return view('affectations.show', ['affectation' => $affectation, 'elem_arr' => $elem_arr]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Affectation  $affectation
     * @return \Illuminate\Http\Response
     */
    public function edit(Affectation $affectation)
    {
        //$type_mouvements = TypeMouvement::nonSystem()->get()->pluck('libelle', 'id');
        $affectation = Affectation::with(['statut','affectationarticles','typeAffectation'])->where('id', $affectation->id)->first();
        $elem_arr = $this->getElemArr($affectation->typeAffectation->tags, $affectation->beneficiaire->id);

        $q = null;

        $articles_disponibles = Article::disponibles()->get()->pluck('reference_complete', 'id')->toArray();
        $articles_a_affecter = $affectation->articlesNotEnded()->pluck('reference_complete', 'id')->toArray(); //Article::whereIn('id', $articles_a_affecter_ids)->get()->pluck('reference_complete', 'id')->toArray();

        $nowdate = Carbon::now();

        $articles_a_affecter_json = json_encode($articles_a_affecter);
        $articles_disponibles_json = json_encode($articles_disponibles);

        return view('affectations.edit')
          ->with('articles_disponibles', $articles_disponibles)
          ->with('elem_arr', $elem_arr)
          ->with('articles_a_affecter', $articles_a_affecter)
          ->with('q', $q)
          ->with('nowdate', $nowdate)
          ->with('articles_a_affecter_json', $articles_a_affecter_json)
          ->with('articles_disponibles_json', $articles_disponibles_json)
          //->with('type_mouvements', $type_mouvements)
          ->with('affectation', $affectation)
          ;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Affectation  $affectation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Affectation $affectation)
    {
            //$type_mouvements = TypeMouvement::nonSystem()->get()->pluck('libelle', 'id');
            $type_mouvement = TypeMouvement::modification()->first();//->pluck('libelle', 'id');
            $request->merge([
                'type_mouvement_id' => $type_mouvement->id,
            ]);

            $formInput = $request->all();
            $nowdate = Carbon::now();
            $q = $request->has('q') ? $formInput['q'] : '';

            //dd('Send the form for update', $request);

            if ($request->has('articles_a_affecter'))
              $articles_a_affecter = $formInput['articles_a_affecter'];
            else
              $articles_a_affecter = null;

            if ($request['action'] == 'valider-affectation') {
                // Validate the form
                // dd('Validate the form for update', $request);
                // $request->validate([
                //   'objet' => 'required',
                //   'type_mouvement_id' => 'required',
                //   'details' => 'required',
                //   'articles_a_affecter' => 'required',
                //   'date_debut' => 'required|date',
                // ]);

                //$request->validate(Affectation::updateRules($affectation));
                //$this->validate($request, Affectation::updateRules($affectation));
                $validation = Validator::make($formInput, Affectation::updateRules($affectation));

                // Validator::make($request->all(), [
                //    'comment' => 'required|max:20',
                // ])->validate() ;

                //dd($validation->fails());
                if($validation->fails())
                {
                    //return redirect()->back()->withErrors($validation)->withInput();
                    $results_arr = $this->listArticlesSearch($request, $affectation, '$elem_id', $q);
                    return view('affectations.edit')
                        ->with('articles_disponibles', $results_arr['articles_disponibles'])
                        ->with('articles_a_affecter', $results_arr['articles_a_affecter'])
                        ->with('articles_disponibles_json', $results_arr['articles_disponibles_json'])
                        ->with('articles_a_affecter_json', $results_arr['articles_a_affecter_json'])
                        ->with('elem_arr', $results_arr['elem_arr'])
                        ->with('q', $q)
                        ->with('nowdate', $nowdate)
                        //->with('type_mouvements', $type_mouvements)
                        ->with('affectation', $affectation)
                        ->withErrors($validation)
                      ;
                }
                // dd($validation->fails());
                // return View::make('admin.articleedit')->with('article', $article)->with('flash_message', 'There were validation errors.');

            } else {
              if ($request['action'] == 'search-articles') {
                  $results_arr = $this->listArticlesSearch($request, $affectation, '$elem_id', $q);
              } elseif ($request['action'] == 'add-articles') {
                  $results_arr = $this->listArticlesAdd($request, $affectation, '$elem_id', $q);
              } elseif($request['action'] == 'remove-articles') {
                    $results_arr = $this->listArticlesRemove($request, $affectation, '$elem_id', $q);
              } else {

              }

              return view('affectations.edit')
                  ->with('articles_disponibles', $results_arr['articles_disponibles'])
                  ->with('articles_a_affecter', $results_arr['articles_a_affecter'])
                  ->with('articles_disponibles_json', $results_arr['articles_disponibles_json'])
                  ->with('articles_a_affecter_json', $results_arr['articles_a_affecter_json'])
                  ->with('elem_arr', $results_arr['elem_arr'])
                  ->with('q', $q)
                  ->with('nowdate', $nowdate)
                  //->with('type_mouvements', $type_mouvements)
                  ->with('affectation', $affectation)
                ;
            }

            //dd('Form validated for update', $request);

            $liste_article_a_retirer = Article::join("affectation_article","affectation_article.article_id","=","articles.id")
                ->where("affectation_article.affectation_id", $affectation->id)
                ->get()
                ->pluck('article_id')->toArray();

            $articles_a_affecter = json_decode($formInput['articles_a_affecter'], true);

            $type_mouvement_id = $type_mouvement->id;//$formInput['type_mouvement_id'];
            $details = $formInput['details'];
            $elem_id = $formInput['elem_id'];

            //dd($formInput,$articles_a_affecter,$liste_article_a_retirer);

            // Retrait des entrees non contenues dans la table affectation
            $formInput = $this->formatRequestInput($formInput);

            $upd_rst = $this->updateOne($affectation->id, $formInput, $type_mouvement_id, $details, $articles_a_affecter, $liste_article_a_retirer);

            // Sessions Message
            if ($upd_rst == 1) {
              $request->session()->flash('msg_success',__('messages.affectationUpdated', ['affectationtype' => $affectation->typeAffectation->libelle] ));
            } else {
              $request->session()->flash('msg_danger',__('messages.affectationCantBeEmpty'));
            }

            $show_controller = $affectation->typeAffectation->tags . 'Controller@' . 'show';

            return redirect()->action($show_controller, $affectation->beneficiaire->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Affectation  $affectation
     * @return \Illuminate\Http\Response
     */
    public function destroy(Affectation $affectation)
    {
        $type_affectation = TypeAffectation::find($affectation->type_affectation_id);
        $this->deleteOne($affectation->id);

        // Sessions Message
        session()->flash('msg_success',__('messages.affectationDeleted', ['affectationtype' => $type_affectation->libelle] ));

        $show_controller = $affectation->typeAffectation->tags . 'Controller@' . 'show';

        return redirect()->action($show_controller, $affectation->beneficiaire->id);
    }

    // Affectations Elem
    public function elemcreate($type_affectation_tag, $elem_id)
    {
        $q = null;
        $type_affectation = TypeAffectation::tagged($type_affectation_tag)->first();
        $elem_arr = $this->getElemArr($type_affectation_tag, $elem_id);
        $articles_disponibles = Article::disponibles()->get()->pluck('reference_complete', 'id')->toArray();
        $nowdate = Carbon::now();

        $articles_a_affecter = null;
        $articles_a_affecter_json = null;
        $articles_disponibles_json = json_encode($articles_disponibles);

        return view('affectations.elemcreate')
          ->with('articles_disponibles', $articles_disponibles)
          ->with('type_affectation', $type_affectation)
          ->with('elem_arr', $elem_arr)
          ->with('articles_a_affecter', $articles_a_affecter)
          ->with('q', $q)
          ->with('nowdate', $nowdate)
          ->with('articles_a_affecter_json', $articles_a_affecter_json)
          ->with('articles_disponibles_json', $articles_disponibles_json)
          ;
    }

    public function elemstore(Request $request, $type_affectation_tag, $elem_id)
    {
        // Validate the form
        $formInput = $request->all();
        $nowdate = Carbon::now();
        $q = $request->has('q') ? $formInput['q'] : '';

        $affectation = new Affectation();
        $affectation->objet = $request->has('objet') ? $formInput['objet'] : '';

        if ($request->has('articles_a_affecter'))
          $articles_a_affecter = $formInput['articles_a_affecter'];
        else
          $articles_a_affecter = null;

        if ($request['action'] == 'valider-affectation') {
            //dd('Validate the form for create', $request);
            // $request->validate([
            //   'objet' => 'required',
            //   'articles_a_affecter' => 'required',
            //   'date_debut' => 'required|date',
            // ]);
            $request->validate(Affectation::createRules());
        } else {
            if ($request['action'] == 'search-articles') {
                $results_arr = $this->listArticlesSearch($request, $type_affectation_tag, $elem_id, $q);
            } elseif ($request['action'] == 'add-articles') {
            	  $results_arr = $this->listArticlesAdd($request, $type_affectation_tag, $elem_id, $q);
            } elseif ($request['action'] == 'remove-articles') {
                  $results_arr = $this->listArticlesRemove($request, $type_affectation_tag, $elem_id, $q);
            } else {

            }

            return view('affectations.elemcreate')
                ->with('articles_disponibles', $results_arr['articles_disponibles'])
                ->with('articles_a_affecter', $results_arr['articles_a_affecter'])
                ->with('articles_disponibles_json', $results_arr['articles_disponibles_json'])
                ->with('articles_a_affecter_json', $results_arr['articles_a_affecter_json'])
                ->with('type_affectation', $results_arr['type_affectation'])
                ->with('elem_arr', $results_arr['elem_arr'])
                ->with('q', $q)
                ->with('affectation', $affectation)
                ->with('nowdate', $nowdate)
              ;
        }

        $type_affectation = TypeAffectation::tagged($type_affectation_tag)->first();
        $formInput = $request->all();
        $articles_a_affecter = json_decode($formInput['articles_a_affecter'], true);

        $this->createNew($formInput['objet'], $formInput['date_debut'], $type_affectation_tag, $elem_id ,$articles_a_affecter);

        // Sessions Message
        $request->session()->flash('msg_success',__('messages.affectationCreated', ['affectationtype' => $type_affectation->libelle] ));

        $show_controller = $type_affectation_tag . 'Controller@' . 'show';

        return redirect()->action($show_controller, $elem_id);
    }

    private function convert_data_to_html($affectation) {
      $data = Affectation::where('tags', 'NOT LIKE', "%Systeme%")->OrWhereNull('tags')->get();
      $affectation = Affectation::find($affectation);

      $gt_logo_url = public_path()  . "/assets/images/logo_gt.jpg";//url("assets/images/gt.png");
      $inter_ligne = '--------------------------------------';
      //dd($gt_logo_url);

      $output = '
        <img height="60" src="'. $gt_logo_url .'" class="ribbon"/>
        <p align="center">GABON TELECOM<br/>
        '.$inter_ligne.'<br/>
        DIRECTION RESEAUX<br/>
        '.$inter_ligne.'<br/>
        DIVISION DES SYSTEMES D INFORMATION<br/>
        '.$inter_ligne.'<br/>
        SI PRODUCTION<br/>
        </p>
        <h3 align="center">Affectation '.$affectation->typeaffectation->tags.'</h3>
        <p align="left"><strong>Objet:</strong> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$affectation->objet.'<br/>
        <strong>Bénéficiaire:</strong> &nbsp;'.$affectation->beneficiaire->denomination.'<br/>
        </p>
        <hr>
        <h5 align="left">Liste des articles</h5>
        <table width="100%" style="border-collapse: collapse; border: 0px;">
        <tr>
        <th style="border: 1px solid; padding:12px;" width="15%">Référence</th>
        <th style="border: 1px solid; padding:12px;" width="15%">Marque</th>
        <th style="border: 1px solid; padding:12px;" width="15%">Etat</th>
        </tr>';
      foreach($affectation->affectationarticles as $affectationarticle) {
        if($affectationarticle->date_fin) {

        } else {
          $output.= '
            <tr>
            <td style="border: 1px solid; padding:12px;">'.$affectationarticle->article->reference_complete.'</td>
            <td style="border: 1px solid; padding:12px;">'.$affectationarticle->article->marqueArticle->denomination.'</td>
            <td style="border: 1px solid; padding:12px;">'.$affectationarticle->article->etatArticle->libelle.'</td>
            </tr>';
        }
      }
      $output.='</table>';
      return $output;
    }

    function pdf($affectation) {
      //dd($affectation);
      $pdf = \App::make('dompdf.wrapper');
      $pdf->loadHTML($this->convert_data_to_html($affectation));
      return $pdf->stream();
    }
}
