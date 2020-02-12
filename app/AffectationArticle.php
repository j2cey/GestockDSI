<?php

namespace App;

// use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;


class AffectationArticle extends Model
{
    public $incrementing = true;
    protected $table = 'affectation_article';

    protected $guarded = [];

    public function article() {
        return $this->belongsTo('App\Article');
    }

    public function affectation()
    {
        return $this->belongsTo('App\Affectation');
    }

    public function typemouvement()
    {
        return $this->belongsTo('App\TypeMouvement','type_mouvement_id');
    }

    public function stockemplacement()
    {
      return $this->belongsTo('App\StockEmplacement', 'stock_emplacement_id');
    }

    public function prev_affectationarticle() {
        return $this->belongsTo('App\Article', 'prev_affectationarticle_id');
    }

    public function next_affectationarticle() {
        return $this->belongsTo('App\AffectationArticle', 'next_affectationarticle_id');
    }

    public function getDurationAttribute() {
      $starttime = Carbon::parse($this->date_debut);
      if ($this->date_fin) {
        $endtime = Carbon::parse($this->date_fin);
      } else {
        $endtime = Carbon::now();
      }
      $affectationduration_c = $starttime->diff($endtime);
      $affectationduration_str = $affectationduration_c->days . ' tots, ' . $affectationduration_c->y . ' ans, ' . $affectationduration_c->m . ' ms, ' . $affectationduration_c->d . ' jrs, ' . $affectationduration_c->h . ' hrs, ' . $affectationduration_c->i . ' mns, ' . $affectationduration_c->s . ' secs';
      //return  $affectationduration_c->format('%y ans, %m mois, %d jrs, %h hrs, %i mns, %s secs');
      return  $affectationduration_c;
    }

    public function terminer($details_fin)
    {
        $this->date_fin = now();
        $this->details_fin = $details_fin;
        $this->save();
    }
}
