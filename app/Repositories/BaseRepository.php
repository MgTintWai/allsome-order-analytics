<?php

namespace App\Repositories;

use App\Contracts\BaseInterface;
use App\Exceptions\RepositoryException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Generic Eloquent CRUD. Transactions belong in the service layer when multiple steps run together.
 */
abstract class BaseRepository implements BaseInterface
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function all(): Collection
    {
        return $this->execute(
            fn () => $this->model->all(),
            'fetch all records'
        );
    }

    public function findById(int $id): ?Model
    {
        return $this->execute(
            fn () => $this->model->find($id),
            "fetch record with ID {$id}"
        );
    }

    public function create(array $data): Model
    {
        return $this->execute(
            fn () => $this->model->create($data),
            'create record'
        );
    }

    public function update(int $id, array $data): Model
    {
        return $this->execute(
            function () use ($id, $data) {
                $record = $this->model->findOrFail($id);
                $record->update($data);

                return $record;
            },
            "update record with ID {$id}"
        );
    }

    public function delete(int $id): bool
    {
        return $this->execute(
            fn () => $this->model->findOrFail($id)->delete(),
            "delete record with ID {$id}"
        );
    }

    /**
     * Runs a callback and maps unexpected failures to {check RepositoryException}.
     * Laravel HTTP-layer exceptions are rethrown so the framework can render correct status codes.
     */
    protected function execute(callable $callback, string $action)
    {
        try {
            return $callback();
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (ValidationException $e) {
            throw $e;
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error("Repository failed to {$action}", [
                'model' => get_class($this->model),
                'exception' => $e,
            ]);

            throw new RepositoryException(
                "Unable to {$action}",
                500,
                $e
            );
        }
    }
}