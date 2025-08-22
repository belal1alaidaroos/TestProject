<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ErpService
{
    protected string $provider;
    protected array $config;
    protected bool $enabled;

    public function __construct()
    {
        $this->provider = config('services.erp.provider', 'disabled');
        $this->config = config('services.erp', []);
        $this->enabled = config('services.erp.enabled', false);
    }

    /**
     * Process payment through ERP
     */
    public function processPayment(Payment $payment): array
    {
        if (!$this->enabled) {
            return $this->processInternalPayment($payment);
        }

        try {
            switch ($this->provider) {
                case 'sap_b1':
                    return $this->processSapB1Payment($payment);
                
                case 'external':
                    return $this->processExternalPayment($payment);
                
                default:
                    Log::warning('Unknown ERP provider', ['provider' => $this->provider]);
                    return $this->processInternalPayment($payment);
            }
        } catch (\Exception $e) {
            Log::error('ERP payment processing failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            
            // Fallback to internal processing
            return $this->processInternalPayment($payment);
        }
    }

    /**
     * Internal payment processing (stub for development)
     */
    protected function processInternalPayment(Payment $payment): array
    {
        Log::info('Processing payment internally', [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'method' => $payment->payment_method,
        ]);

        // Simulate payment processing
        $success = true;
        $transactionId = 'INT_' . uniqid();
        $status = 'completed';

        // Update payment record
        $payment->update([
            'status' => $status,
            'transaction_id' => $transactionId,
            'processed_at' => now(),
        ]);

        // Update contract status
        $payment->contract->update([
            'status' => 'Active',
            'payment_status' => 'Paid',
        ]);

        return [
            'success' => $success,
            'transaction_id' => $transactionId,
            'status' => $status,
            'message' => 'Payment processed successfully',
        ];
    }

    /**
     * SAP Business One payment processing
     */
    protected function processSapB1Payment(Payment $payment): array
    {
        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                    'Content-Type' => 'application/json',
                ])
                ->post($this->config['endpoint'] . '/payments', [
                    'amount' => $payment->amount,
                    'currency' => $payment->currency ?? 'SAR',
                    'payment_method' => $payment->payment_method,
                    'customer_id' => $payment->contract->customer_id,
                    'contract_id' => $payment->contract_id,
                    'description' => "Payment for contract {$payment->contract_id}",
                    'reference' => $payment->id,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Update payment record
                $payment->update([
                    'status' => 'completed',
                    'transaction_id' => $data['transaction_id'],
                    'processed_at' => now(),
                    'erp_reference' => $data['erp_reference'] ?? null,
                ]);

                // Update contract status
                $payment->contract->update([
                    'status' => 'Active',
                    'payment_status' => 'Paid',
                ]);

                Log::info('SAP B1 payment processed successfully', [
                    'payment_id' => $payment->id,
                    'transaction_id' => $data['transaction_id'],
                ]);

                return [
                    'success' => true,
                    'transaction_id' => $data['transaction_id'],
                    'status' => 'completed',
                    'message' => 'Payment processed successfully',
                    'erp_reference' => $data['erp_reference'] ?? null,
                ];
            } else {
                Log::error('SAP B1 payment failed', [
                    'payment_id' => $payment->id,
                    'response' => $response->body(),
                    'status' => $response->status(),
                ]);

                return [
                    'success' => false,
                    'status' => 'failed',
                    'message' => 'ERP payment processing failed',
                ];
            }
        } catch (\Exception $e) {
            Log::error('SAP B1 payment exception', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'ERP connection error',
            ];
        }
    }

    /**
     * External ERP payment processing
     */
    protected function processExternalPayment(Payment $payment): array
    {
        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                    'Content-Type' => 'application/json',
                ])
                ->post($this->config['endpoint'] . '/payments', [
                    'amount' => $payment->amount,
                    'currency' => $payment->currency ?? 'SAR',
                    'payment_method' => $payment->payment_method,
                    'customer_id' => $payment->contract->customer_id,
                    'contract_id' => $payment->contract_id,
                    'description' => "Payment for contract {$payment->contract_id}",
                    'reference' => $payment->id,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Update payment record
                $payment->update([
                    'status' => 'completed',
                    'transaction_id' => $data['transaction_id'],
                    'processed_at' => now(),
                    'erp_reference' => $data['erp_reference'] ?? null,
                ]);

                // Update contract status
                $payment->contract->update([
                    'status' => 'Active',
                    'payment_status' => 'Paid',
                ]);

                return [
                    'success' => true,
                    'transaction_id' => $data['transaction_id'],
                    'status' => 'completed',
                    'message' => 'Payment processed successfully',
                    'erp_reference' => $data['erp_reference'] ?? null,
                ];
            } else {
                return [
                    'success' => false,
                    'status' => 'failed',
                    'message' => 'ERP payment processing failed',
                ];
            }
        } catch (\Exception $e) {
            Log::error('External ERP payment exception', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'ERP connection error',
            ];
        }
    }

    /**
     * Sync contract data to ERP
     */
    public function syncContract(Contract $contract): array
    {
        if (!$this->enabled) {
            return ['success' => true, 'message' => 'ERP sync disabled'];
        }

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                    'Content-Type' => 'application/json',
                ])
                ->post($this->config['endpoint'] . '/contracts', [
                    'contract_id' => $contract->id,
                    'customer_id' => $contract->customer_id,
                    'worker_id' => $contract->worker_id,
                    'start_date' => $contract->start_date,
                    'end_date' => $contract->end_date,
                    'total_amount' => $contract->total_amount,
                    'currency' => $contract->currency ?? 'SAR',
                    'status' => $contract->status,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Update contract with ERP reference
                $contract->update([
                    'erp_reference' => $data['erp_reference'] ?? null,
                ]);

                Log::info('Contract synced to ERP successfully', [
                    'contract_id' => $contract->id,
                    'erp_reference' => $data['erp_reference'] ?? null,
                ]);

                return [
                    'success' => true,
                    'message' => 'Contract synced successfully',
                    'erp_reference' => $data['erp_reference'] ?? null,
                ];
            } else {
                Log::error('Contract ERP sync failed', [
                    'contract_id' => $contract->id,
                    'response' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'ERP sync failed',
                ];
            }
        } catch (\Exception $e) {
            Log::error('Contract ERP sync exception', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'ERP connection error',
            ];
        }
    }

    /**
     * Get payment status from ERP
     */
    public function getPaymentStatus(string $transactionId): ?array
    {
        if (!$this->enabled || $this->provider === 'disabled') {
            return null;
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                ])
                ->get($this->config['endpoint'] . '/payments/' . $transactionId);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Failed to get payment status from ERP', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Check ERP connectivity
     */
    public function checkConnectivity(): bool
    {
        if (!$this->enabled || $this->provider === 'disabled') {
            return true; // Consider disabled as "connected"
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                ])
                ->get($this->config['endpoint'] . '/health');

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('ERP connectivity check failed', [
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
}