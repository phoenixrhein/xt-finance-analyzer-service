<?php

namespace de\xovatec\financeAnalyzer\Helpers;

use Illuminate\Database\Eloquent\Builder;

class CopyBuilderQueryHelper
{
    /**
     *
     * @param Builder $source
     * @return Builder
     */
    public static function copy(Builder $source): Builder
    {
        $model = $source->getModel();
        $newBuilder = $model->newQuery();

        $newBuilder->getQuery()->wheres = $source->getQuery()->wheres;
        $newBuilder->getQuery()->orders = $source->getQuery()->orders ?? [];
        $newBuilder->getQuery()->joins = $source->getQuery()->joins ?? [];
        if ($source->getQuery()->groups) {
            $newBuilder->getQuery()->groups = $source->getQuery()->groups;
        }
        if ($source->getQuery()->columns) {
            $newBuilder->getQuery()->columns = $source->getQuery()->columns;
        }
        if ($source->getQuery()->havings) {
            $newBuilder->getQuery()->havings = $source->getQuery()->havings;
        }
        $newBuilder->getQuery()->limit = $source->getQuery()->limit;
        $newBuilder->getQuery()->offset = $source->getQuery()->offset;

        $newBuilder->getQuery()->setBindings($source->getQuery()->getBindings());

        return $newBuilder;
    }
}
