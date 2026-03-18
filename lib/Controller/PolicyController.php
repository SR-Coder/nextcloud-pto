<?php

declare(strict_types=1);

namespace OCA\PTO\Controller;

use OCA\PTO\AppInfo\Application;
use OCA\PTO\Service\PolicyService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class PolicyController extends Controller {
    private PolicyService $service;

    public function __construct(IRequest $request, PolicyService $service) {
        parent::__construct(Application::APP_ID, $request);
        $this->service = $service;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): DataResponse {
        $policies = $this->service->findAll();
        return new DataResponse($policies);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function show(int $id): DataResponse {
        try {
            $policy = $this->service->find($id);
            return new DataResponse($policy);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function create(
        string $name,
        string $type,
        ?float $accrualRate = null,
        ?string $accrualPeriod = null,
        ?float $maxBalance = null,
        ?float $fixedAnnualHours = null,
        ?string $resetDate = null
    ): DataResponse {
        try {
            // TODO: Check admin permission
            $policy = $this->service->create(
                $name,
                $type,
                $accrualRate,
                $accrualPeriod,
                $maxBalance,
                $fixedAnnualHours,
                $resetDate
            );

            return new DataResponse($policy, Http::STATUS_CREATED);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function update(
        int $id,
        ?string $name = null,
        ?string $type = null,
        ?float $accrualRate = null,
        ?string $accrualPeriod = null,
        ?float $maxBalance = null,
        ?float $fixedAnnualHours = null,
        ?string $resetDate = null,
        ?bool $enabled = null
    ): DataResponse {
        try {
            // TODO: Check admin permission
            $policy = $this->service->update(
                $id,
                $name,
                $type,
                $accrualRate,
                $accrualPeriod,
                $maxBalance,
                $fixedAnnualHours,
                $resetDate,
                $enabled
            );

            return new DataResponse($policy);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function destroy(int $id): DataResponse {
        try {
            // TODO: Check admin permission
            $this->service->delete($id);
            return new DataResponse([], Http::STATUS_NO_CONTENT);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        }
    }
}
