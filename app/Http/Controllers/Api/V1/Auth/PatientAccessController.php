<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PatientRedeemRequest;
use App\Http\Resources\MedicalSessionResource;
use App\Models\MedicalSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PatientAccessController extends Controller
{
    /**
     * Canjea un código de atención (CTA) por un token de acceso
     * acotado a la sesión médica que ese código abrió.
     */
    public function redeem(PatientRedeemRequest $request): JsonResponse
    {
        $ctaCode = $request->string('ctaCode')->trim()->upper()->value();

        $session = MedicalSession::query()
            ->with(['patient', 'healthCenter', 'unit', 'creator', 'closer'])
            ->where('cta_code', $ctaCode)
            ->first();

        if (! $session || ! $session->status->isOpen()) {
            throw new ApiException(
                errorCode: 'INVALID_CODE',
                message: 'El código ingresado no existe, expiró o está bloqueado.',
                statusCode: 422,
            );
        }

        $patient = $session->patient;

        return DB::transaction(function () use ($patient, $session) {
            // Un dispositivo a la vez: se revoca cualquier token anterior.
            $patient->tokens()->delete();

            $token = $patient->createToken(
                name: 'patient-portal',
                abilities: ["session:{$session->id}"],
            );

            return response()->json([
                'status' => 'success',
                'data' => [
                    'token'     => $token->plainTextToken,
                    'tokenType' => 'Bearer',
                    'session'   => new MedicalSessionResource($session),
                ],
            ]);
        });
    }

    /**
     * Revoca el token actual del paciente (logout del portal).
     */
    public function logout(): JsonResponse
    {
        $request = request();
        $request->user()->currentAccessToken()->delete();

        return response()->json(['status' => 'success']);
    }
}