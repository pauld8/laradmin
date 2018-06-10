<?php

namespace Shemi\Laradmin\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Shemi\Laradmin\Contracts\Repositories\SyncMediaRepository as SyncMediaRepositoryContract;
use Shemi\Laradmin\Exceptions\SyncMedia\CantDeleteMediaException;
use Shemi\Laradmin\Exceptions\SyncMedia\MediaModelNotFoundException;
use Shemi\Laradmin\Exceptions\SyncMedia\ModelMustImplementHasMediaException;
use Shemi\Laradmin\Exceptions\SyncMedia\UnableToClearMediaException;
use Shemi\Laradmin\Exceptions\SyncMedia\UnableToSaveMediaException;
use Shemi\Laradmin\Models\Field;
use Spatie\MediaLibrary\HasMedia\Interfaces\HasMedia;
use Spatie\MediaLibrary\Media;

class SyncMediaRepository implements SyncMediaRepositoryContract
{
    /**
     * @var Collection $new
     */
    protected $new;

    /**
     * @var Collection $current
     */
    protected $current;

    /**
     * @var Collection $delete
     */
    protected $delete;

    /**
     * @var Collection $update
     */
    protected $update;

    /**
     * @var Collection $create
     */
    protected $create;

    /**
     * @var string $collection
     */
    protected $collection;

    /**
     * @var HasMedia $model
     */
    protected $model;

    /**
     * @var Field $field
     */
    protected $field;

    /**
     * @var array $currentIds
     */
    protected $currentIds;

    public function __construct()
    {
        $this->new = new Collection();
        $this->current = new Collection();
        $this->delete = new Collection();
        $this->update = new Collection();
        $this->create = new Collection();
    }

    /**
     * @param $new
     * @param Model $model
     * @param Field $field
     * @return $this
     * @throws CantDeleteMediaException
     * @throws MediaModelNotFoundException
     * @throws ModelMustImplementHasMediaException
     * @throws UnableToClearMediaException
     * @throws UnableToSaveMediaException
     */
    public function sync($new, Model $model, Field $field)
    {
        $this->new = $new instanceof Collection ? $new : new Collection($new);
        $this->model = $model;
        $this->field = $field;
        $this->collection = $this->field->media_collection;
        $this->currentIds = [];

        if(! $model instanceof HasMedia) {
            throw ModelMustImplementHasMediaException::create($model);
        }

        if(! $model->exists) {
            $model->save();
        } else {
            $this->current = $model->getMedia($this->collection);
        }

        if($this->new->isEmpty() && $this->current->isNotEmpty()) {
            $this->clearAll();

            return $this;
        }


        foreach ($this->new as $media) {
            if($media->is_new) {
                $this->create->push($media);
            } else {
                $this->update->push($media);
            }
        }


        $updateIds = $this->update->pluck('id');

        foreach ($this->current as $media) {
            if(! $updateIds->contains($media->id)) {
                $this->delete->push($media);
            }
        }

        return $this->delete()
            ->update()
            ->create();
    }

    /**
     * @throws UnableToClearMediaException
     */
    protected function clearAll()
    {
        try {
            $this->model->clearMediaCollection();
        }
        catch (\Exception $exception) {
            throw UnableToClearMediaException::create($exception);
        }
    }

    /**
     * @return $this
     * @throws CantDeleteMediaException
     */
    protected function delete()
    {
        if($this->delete->isEmpty()) {
            return $this;
        }

        foreach ($this->delete as $media) {
            $this->deleteMedia($media);
        }

        return $this;
    }

    /**
     * @return $this
     * @throws MediaModelNotFoundException
     * @throws UnableToSaveMediaException
     */
    protected function update()
    {
        if($this->update->isEmpty()) {
            return $this;
        }

        foreach ($this->update as $media) {
            $mediaModel = $media;

            if(! $media instanceof Media) {
                $mediaModel = $this->current->first(function($mediaModel) use ($media) {
                    return $media->id === $mediaModel->id;
                });
            }

            if(! $mediaModel instanceof Media) {
                throw MediaModelNotFoundException::create();
            }

            $mediaModel->setAttribute("name", $media->name ?: $mediaModel->name);
            $mediaModel->setAttribute("order_column", $media->order);
            $mediaModel->setAttribute('collection_name', $this->collection);
            $mediaModel->setCustomProperty('alt', $media->alt);
            $mediaModel->setCustomProperty('caption', $media->caption);

            $this->saveMedia($mediaModel);
        }

        return $this;
    }

    /**
     * @return $this
     * @throws UnableToSaveMediaException
     */
    protected function create()
    {
        if($this->create->isEmpty()) {
            return $this;
        }

        foreach ($this->create as $media) {
            try {
                $mediaModel = $this->model
                    ->addMedia(storage_path('app/'.$media->temp_path))
                    ->usingName($media->name)
                    ->withCustomProperties([
                        'alt' => $media->alt,
                        'caption' => $media->caption
                    ])
                    ->toMediaCollection($this->collection, $this->field->media_disk);
            }
            catch (\Throwable $exception) {
                throw UnableToSaveMediaException::create(
                    $media->name,
                    $this->field->key,
                    $this->field->media_disk,
                    $exception
                );
            }

            $mediaModel->setAttribute("order_column", $media->order);
            $this->saveMedia($mediaModel);
        }

        return $this;
    }

    /**
     * @param Media $media
     * @return bool
     * @throws CantDeleteMediaException
     */
    protected function deleteMedia(Media $media)
    {
        try {
            $media->delete();
        }

        catch (\Exception $exception) {
            throw CantDeleteMediaException::create($media->id, $this->field->key, $exception);
        }

        return true;
    }

    /**
     * @param Media $media
     * @return bool
     * @throws UnableToSaveMediaException
     */
    protected function saveMedia(Media $media)
    {
        try {
            $media->saveOrFail();

            $this->currentIds[] = $media->getKey();
        }

        catch (\Throwable $exception) {
            throw UnableToSaveMediaException::create(
                $media->name,
                $media->collection_name,
                $media->disk,
                $exception
            );
        }

        $this->current->push($media);

        return true;
    }

    /**
     * @return array
     */
    public function getCurrentIds()
    {
        return $this->currentIds;
    }

    public function fresh()
    {
        return new static;
    }

}