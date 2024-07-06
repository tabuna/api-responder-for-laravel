<?php

declare(strict_types=1);

namespace Pepperfm\ApiBaseResponder;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Pepperfm\ApiBaseResponder\Attributes\ValidateRestMethod;
use Pepperfm\ApiBaseResponder\Contracts\ResponseContract;

class ApiBaseResponder implements ResponseContract
{
    /** @var array<string, string> $headers */
    private array $headers = ['Content-Type' => 'application/json; charset=UTF-8', 'charset' => 'utf-8'];

    public static function make(): static
    {
        return new static();
    }

    /**
     * @inheritdoc
     */
    #[ValidateRestMethod]
    public function response(
        array|Collection $data,
        array $meta = [],
        string $message = 'Success',
        int $httpStatusCode = JsonResponse::HTTP_OK
    ): JsonResponse {
        $callerFunctionName = data_get(debug_backtrace(), '1.function');
        /** @var ValidateRestMethod $attribute */
        $attribute = head((new \ReflectionClass($this))->getMethod('response')->getAttributes())->newInstance();
        $key = $attribute->getDataKey($callerFunctionName);

        return response()->json([
            $key => $data,
            'meta' => $meta,
            'message' => $message,
        ], $httpStatusCode, $this->headers, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @inheritdoc
     */
    public function error(
        string $message = 'Error',
        int $httpStatusCode = JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
        mixed $errors = null,
        mixed $data = null
    ): JsonResponse {
        return response()->json([
            'entities' => $data,
            'message' => $message,
            'errors' => $errors,
        ], $httpStatusCode, $this->headers, JSON_UNESCAPED_UNICODE);
    }

    public function paginated(
        array|\Illuminate\Pagination\LengthAwarePaginator $data,
        array|\Illuminate\Pagination\LengthAwarePaginator $meta = [],
        string $message = 'Success',
        int $httpStatusCode = JsonResponse::HTTP_OK
    ): JsonResponse {
        $metaData = rescue(new MetaResolver($data, $meta));

        return $this->response($metaData['data'], $metaData['meta'], $message, $httpStatusCode);
    }

    /**
     * @param array $data
     * @param array $meta
     * @param string $message
     *
     * @return JsonResponse
     */
    public function stored(
        array $data = [],
        array $meta = [],
        string $message = 'Stored',
    ): JsonResponse {
        return $this->response($data, $meta, $message, JsonResponse::HTTP_CREATED);
    }

    /**
     * @param array $data
     * @param string $message
     *
     * @return JsonResponse
     */
    public function deleted(
        array $data = [],
        string $message = 'Deleted',
    ): JsonResponse {
        return $this->response($data, message: $message, httpStatusCode: JsonResponse::HTTP_NO_CONTENT);
    }
}
