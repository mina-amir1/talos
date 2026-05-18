<?php

namespace App\Http\Middleware;

use App\Models\TalosApiToken;
use App\Services\ContentTypeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TalosApiAuth
{
    public function __construct(private ContentTypeService $typeService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $raw = $request->bearerToken()
            ?? $request->header('X-Talos-Token')
            ?? $request->query('token');

        if (! $raw) {
            return response()->json(['error' => 'Unauthenticated. Provide a Bearer token.'], 401);
        }

        $token = TalosApiToken::where('token', hash('sha256', $raw))->first();

        if (! $token || $token->isExpired()) {
            return response()->json(['error' => 'Invalid or expired API token.'], 401);
        }

        if (! $this->isAllowed($request, $token)) {
            return response()->json(['error' => 'Forbidden. This token does not have permission for this operation.'], 403);
        }

        $token->update(['last_used_at' => now()]);
        $request->attributes->set('talos_token', $token);

        return $next($request);
    }

    private function isAllowed(Request $request, TalosApiToken $token): bool
    {
        if ($token->type === 'full-access') {
            return true;
        }

        $operation = $this->resolveOperation($request);

        if ($token->type === 'read-only') {
            return in_array($operation, ['find', 'findOne']);
        }

        // custom — check per-collection permissions
        $name = $request->route('name');
        if (! $name) {
            return false;
        }

        $uid = $this->resolveUid($name);

        // Unknown content type — let the controller handle the 404
        if (! $uid) {
            return true;
        }

        $allowed = ($token->permissions ?? [])[$uid] ?? [];

        return in_array($operation, $allowed);
    }

    private function resolveOperation(Request $request): string
    {
        $method = strtoupper($request->method());
        $hasId  = ! is_null($request->route('id'));

        return match (true) {
            $method === 'GET' && ! $hasId          => 'find',
            $method === 'GET' && $hasId             => 'findOne',
            $method === 'POST'                      => 'create',
            in_array($method, ['PUT', 'PATCH'])     => 'update',
            $method === 'DELETE'                    => 'delete',
            default                                 => 'find',
        };
    }

    private function resolveUid(string $name): ?string
    {
        foreach ($this->typeService->all() as $type) {
            $isSingle = ($type['kind'] ?? 'collectionType') === 'singleType';
            $match    = $isSingle
                ? ($type['info']['singularName'] ?? '')
                : ($type['info']['pluralName'] ?? '');

            if ($match === $name) {
                return $type['__uid'];
            }
        }

        return null;
    }
}
