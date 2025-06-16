<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class FacturacionControllerTest extends TestCase
{
    /** @test */
    public function puede_emitir_una_boleta_electronica()
    {
        $payload = [
            "boleta_numero" => "B001-99999999",
            "boleta_fecha" => now()->toIso8601String(),
            "boleta_subtotal" => 4.20,
            "boleta_impuestos" => 0.76,
            "boleta_total" => 4.96,
            "metodos_pago" => [
                [ "met_nombre" => "Contado" ]
            ],
            "pedido" => [
                "cliente" => [
                    "cli_tipo_doc" => "1",
                    "cli_numero_doc" => "00000000",
                    "cli_nombre" => "Carlos",
                    "cli_apellido" => "Genérico"
                ],
                "detalles" => [
                    [
                        "det_cantidad" => 1,
                        "det_precio_unitario" => 1.02,
                        "det_subtotal" => 1.02,
                        "det_impuesto" => 0.18,
                        "producto" => [
                            "pro_id" => "PROD-004",
                            "pro_nombre" => "Papaya"
                        ]
                    ],
                    [
                        "det_cantidad" => 1,
                        "det_precio_unitario" => 2.54,
                        "det_subtotal" => 2.54,
                        "det_impuesto" => 0.46,
                        "producto" => [
                            "pro_id" => "PROD-006",
                            "pro_nombre" => "Fanta"
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/facturacion/emitir', $payload);

        $response->dump(); // Te muestra la respuesta completa en consola
        $response->assertStatus(200); // Si esperas 200 OK
        $response->assertJsonStructure([
            'success',
            'message',
        ]);
    }
}
