<?php

declare(strict_types=1);

namespace Altek\Eventually\Relations\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as BaseCollection;

trait InteractsWithPivotTable
{
    /**
     * Get additional pivot data.
     *
     * @param mixed $id
     * @param array $attributes
     *
     * @return array
     */
    protected function getPivotData($id, array $attributes = []): array
    {
        if ($id instanceof Model) {
            return [
                $id->getKey() => $attributes,
            ];
        }

        if ($id instanceof Collection) {
            return $id->mapWithKeys(function (Model $model) use ($attributes) {
                return [
                    $model->getKey() => $attributes,
                ];
            })->all();
        }

        if ($id instanceof BaseCollection) {
            return $id->mapWithKeys(function ($item) use ($attributes) {
                return [
                    $item => $attributes,
                ];
            })->all();
        }

        $data = [];

        foreach ((array) $id as $key => $value) {
            if (is_array($value)) {
                $data[$key] = array_merge($attributes, $value);
            } else {
                $data[$value] = $attributes;
            }
        }

        return $data;
    }

    /**
     * Toggles a model (or models) from the parent.
     *
     * Each existing model is detached, and non existing ones are attached.
     *
     * @param mixed $ids
     * @param bool  $touch
     *
     * @return array|bool
     */
    public function toggle($ids, $touch = true)
    {
        $data = $this->getPivotData($ids);

        if ($this->parent->firePivotEvent('toggling', true, $this->getRelationName(), $data) === false) {
            return false;
        }

        $changes = parent::toggle($ids, $touch);

        $this->parent->firePivotEvent('toggled', false, $this->getRelationName(), $data);

        return $changes;
    }

    /**
     * Sync the intermediate tables with a list of IDs or collection of models.
     *
     * @param mixed $ids
     * @param bool  $detaching
     *
     * @return array|bool
     */
    public function sync($ids, $detaching = true)
    {
        $data = $this->getPivotData($ids);

        if ($this->parent->firePivotEvent('syncing', true, $this->getRelationName(), $data) === false) {
            return false;
        }

        $changes = parent::sync($ids, $detaching);

        $this->parent->firePivotEvent('synced', false, $this->getRelationName(), $data);

        return $changes;
    }

    /**
     * Update an existing pivot record on the table.
     *
     * @param mixed $id
     * @param array $attributes
     * @param bool  $touch
     *
     * @return int|bool
     */
    public function updateExistingPivot($id, array $attributes, $touch = true)
    {
        $data = $this->getPivotData($id, $attributes);

        if ($this->parent->firePivotEvent('updatingExistingPivot', true, $this->getRelationName(), $data) === false) {
            return false;
        }

        $updated = parent::updateExistingPivot($id, $attributes, $touch);

        $this->parent->firePivotEvent('existingPivotUpdated', false, $this->getRelationName(), $data);

        return $updated;
    }

    /**
     * Attach a model to the parent.
     *
     * @param mixed $id
     * @param array $attributes
     * @param bool  $touch
     *
     * @return bool
     */
    public function attach($id, array $attributes = [], $touch = true)
    {
        $data = $this->getPivotData($id, $attributes);

        if ($this->parent->firePivotEvent('attaching', true, $this->getRelationName(), $data) === false) {
            return false;
        }

        parent::attach($id, $attributes, $touch);

        $this->parent->firePivotEvent('attached', false, $this->getRelationName(), $data);

        return true;
    }

    /**
     * Detach models from the relationship.
     *
     * @param mixed $ids
     * @param bool  $touch
     *
     * @return int|bool
     */
    public function detach($ids = null, $touch = true)
    {
        // When the first argument is null, it means that all models will be detached from
        // the relationship, requiring the corresponding ids to be resolved
        $data = $this->getPivotData($ids ?? $this->query->pluck($this->relatedKey)->all());

        if ($this->parent->firePivotEvent('detaching', true, $this->getRelationName(), $data) === false) {
            return false;
        }

        $results = parent::detach($ids, $touch);

        $this->parent->firePivotEvent('detached', false, $this->getRelationName(), $data);

        return $results;
    }
}
