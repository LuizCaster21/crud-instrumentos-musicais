<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\PedalEfeito;

class PedalEfeitoController {
    private PedalEfeito $pedalModel;

    public function __construct() {
        $this->pedalModel = new PedalEfeito();
    }

    /**
     * GET /api/pedais
     * Lista todos os pedais do usuário autenticado
     */
    public function index(Request $request): void {
        $usuarioId = $request->getUsuarioId();
        $pedais = $this->pedalModel->findAllByUsuario($usuarioId);

        Response::success($pedais, 'Pedais de efeitos recuperados com sucesso!');
    }

    /**
     * GET /api/pedais/{id}
     * Busca um pedal específico
     */
    public function show(Request $request): void {
        $usuarioId = $request->getUsuarioId();
        $id = (int) $request->getParam('id');

        $pedal = $this->pedalModel->findByIdAndUsuario($id, $usuarioId);
        if (!$pedal) {
            Response::error('Pedal de efeito não encontrado.', 404);
        }

        Response::success($pedal, 'Pedal de efeito encontrado com sucesso!');
    }

    /**
     * POST /api/pedais
     * Cadastra um novo pedal
     */
    public function store(Request $request): void {
        $usuarioId   = $request->getUsuarioId();
        $marca       = trim((string) $request->get('marca', ''));
        $modelo      = trim((string) $request->get('modelo', ''));
        $tipoEfeito  = trim((string) $request->get('tipo_efeito', ''));
        $tecnologia  = $request->get('tecnologia') ? trim((string) $request->get('tecnologia')) : null;
        $preco       = filter_var($request->get('preco'), FILTER_VALIDATE_FLOAT);
        $estoque     = filter_var($request->get('quantidade_estoque', 0), FILTER_VALIDATE_INT);

        $errors = [];
        if (empty($marca)) {
            $errors['marca'] = 'A marca é obrigatória.';
        }
        if (empty($modelo)) {
            $errors['modelo'] = 'O modelo é obrigatório.';
        }
        if (empty($tipoEfeito)) {
            $errors['tipo_efeito'] = 'O tipo de efeito é obrigatório (ex: Distortion, Overdrive, Delay, Reverb, Multi-efeitos).';
        }
        if ($preco === false || $preco < 0) {
            $errors['preco'] = 'O preço deve ser um valor numérico válido maior ou igual a zero.';
        }
        if ($estoque === false || $estoque < 0) {
            $errors['quantidade_estoque'] = 'O estoque deve ser um número inteiro maior ou igual a zero.';
        }

        if (!empty($errors)) {
            Response::error('Dados inválidos para cadastro do pedal.', 422, $errors);
        }

        $novoId = $this->pedalModel->create(
            $usuarioId,
            $marca,
            $modelo,
            $tipoEfeito,
            $tecnologia,
            (float)$preco,
            (int)$estoque
        );

        $pedalCriado = $this->pedalModel->findByIdAndUsuario($novoId, $usuarioId);
        Response::success($pedalCriado, 'Pedal de efeito cadastrado com sucesso!', 201);
    }

    /**
     * PUT /api/pedais/{id}
     * Atualiza um pedal existente
     */
    public function update(Request $request): void {
        $usuarioId = $request->getUsuarioId();
        $id        = (int) $request->getParam('id');

        $pedal = $this->pedalModel->findByIdAndUsuario($id, $usuarioId);
        if (!$pedal) {
            Response::error('Pedal não encontrado para atualização.', 404);
        }

        $marca      = trim((string) $request->get('marca', $pedal['marca']));
        $modelo     = trim((string) $request->get('modelo', $pedal['modelo']));
        $tipoEfeito = trim((string) $request->get('tipo_efeito', $pedal['tipo_efeito']));
        $tecnologia = $request->get('tecnologia', $pedal['tecnologia']);
        $tecnologia = $tecnologia ? trim((string) $tecnologia) : null;
        $preco      = filter_var($request->get('preco', $pedal['preco']), FILTER_VALIDATE_FLOAT);
        $estoque    = filter_var($request->get('quantidade_estoque', $pedal['quantidade_estoque']), FILTER_VALIDATE_INT);

        $errors = [];
        if (empty($marca)) {
            $errors['marca'] = 'A marca não pode ficar vazia.';
        }
        if (empty($modelo)) {
            $errors['modelo'] = 'O modelo não pode ficar vazio.';
        }
        if (empty($tipoEfeito)) {
            $errors['tipo_efeito'] = 'O tipo de efeito não pode ficar vazio.';
        }
        if ($preco === false || $preco < 0) {
            $errors['preco'] = 'O preço deve ser válido e maior ou igual a zero.';
        }
        if ($estoque === false || $estoque < 0) {
            $errors['quantidade_estoque'] = 'O estoque deve ser um número inteiro maior ou igual a zero.';
        }

        if (!empty($errors)) {
            Response::error('Dados inválidos para atualização.', 422, $errors);
        }

        $this->pedalModel->update(
            $id,
            $usuarioId,
            $marca,
            $modelo,
            $tipoEfeito,
            $tecnologia,
            (float)$preco,
            (int)$estoque
        );

        $pedalAtualizado = $this->pedalModel->findByIdAndUsuario($id, $usuarioId);
        Response::success($pedalAtualizado, 'Pedal de efeito atualizado com sucesso!');
    }

    /**
     * DELETE /api/pedais/{id}
     * Remove um pedal
     */
    public function destroy(Request $request): void {
        $usuarioId = $request->getUsuarioId();
        $id        = (int) $request->getParam('id');

        $deletado = $this->pedalModel->delete($id, $usuarioId);
        if (!$deletado) {
            Response::error('Pedal não encontrado ou já removido.', 404);
        }

        Response::success(null, 'Pedal de efeito removido com sucesso!');
    }
}
