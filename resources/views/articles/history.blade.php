@extends('layouts.app')

@section('page')
  @include('layouts._button_index', ['canlist' => \App\Article::canlist(), 'index_route' => \App\Article::$route_index, 'model' => $article, 'title' => 'articles'])
@endsection

@section('buttons')

@endsection

@section('breadcrumb')
  {{ Breadcrumbs::render('articles.history',$article->id) }}
@endsection

@section('content')

<div class="row">
  <div class="col-12">
    <div class="card m-b-30">
      <div class="card-body">
        <h4 class="mt-0 header-title">Historique</h4>
          <p class="text-muted m-b-30 font-14">Historique de l article <code class="highlighter-rouge">{{ ucfirst($article->reference_complete) }}</code>.</p>

          <div class="row">
            @include('layouts.message')
          </div>

          <!-- Panel de recherche -->
          <div class="row">

          </div>
          <!-- Fin Panel de recherche -->

          <div class="row">

            <table class="table table-hover table-sm">
              <thead class="thead-default">
                  <tr>
                    <th class="font-weight-bold">#</th>
                    <th class="font-weight-bold">Situation</th>
                    <th class="font-weight-bold">Date Début Mouvement</th>
                    <th class="font-weight-bold">Détails Début Mouvement</th>
                    <th class="font-weight-bold">Date Fin Mouvement</th>
                    <th class="font-weight-bold">Détails Fin Mouvement</th>
                    <th class="font-weight-bold">Durée</th>
                  </tr>
              </thead>
              <tbody>
                @forelse ($article->affectationarticles as $affectationarticle)
                  <tr>

                    <td class="font-weight-bold text-left">{{ $affectationarticle->id }}</td>
                    <td class="font-weight-bold text-left">{{ $affectationarticle->affectation->typeAffectation->tags }}</td>
                    <td class="text-left">{{ date('d-m-Y H:i:s', strtotime($affectationarticle->date_debut)) }}</td>
                    <td class="text-left">
                      {{ $affectationarticle->details_debut }}
                      @if($affectationarticle->affectation->typeAffectation->tags == 'Stock')

                      @else
                      - {{ \setting()->get('history_beneficiaire_prefixe') }} :
                      <a href="{{ action($affectationarticle->affectation->typeAffectation->object_class_name::$route_show, $affectationarticle->affectation->beneficiaire_id) }}">
                        {{ $affectationarticle->affectation->beneficiaire->denomination }}
                      </a>
                      @endif
                    </td>
                    <td class="text-left">{{ $affectationarticle->date_fin ? date('d-m-Y H:i:s', strtotime($affectationarticle->date_fin)) : '' }}</td>
                    <td class="text-left">{{ $affectationarticle->details_fin ?? '' }}</td>
                    <td class="text-left">{{ $affectationarticle->duration ? $affectationarticle->duration->format('%y ans, %m mois, %d jrs, %h hrs, %i mns, %s secs') : '' }}</td>

                  </tr>
                  @empty
                @endforelse
              </tbody>
            </table>

          </div>
        </div>
      </div>
    </div>
  </div>

@endsection

@section('js')
    @include('statuts.change_js')
@endsection
