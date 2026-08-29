<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Instrumento;

class InstrumentoController {
    private Instrumento $instrumentoModel;

    public function __construct() {
        $this->instrumentoModel = new Instrumento();
    }

    /**
     * GET /api/instrumentos
     * Lista todos os instrumentos do usuário autenticado
     */
    public function index(Request $request): void {
        $usuarioId = $request->getUsuarioId();
        $instrumentos = $this->instrumentoModel->findAllByUsuario($usuarioId);

        Response::success($instrumentos, 'Instrumentos recuperados com sucesso!');
    }

    /**
     * GET /api/instrumentos/{id}
     * Busca um instrumento específico
     */
    public function show(Request $request): void {
        $usuarioId = $request->getUsuarioId();
        $id = (int) $request->getParam('id');

        $instrumento = $this->instrumentoModel->findByIdAndUsuario($id, $usuarioId);
        if (!$instrumento) {
            Response::error('Instrumento não encontrado.', 404);
        }

        Response::success($instrumento, 'Instrumento encontrado com sucesso!');
    }

    /**
     * POST /api/instrumentos
     * Cadastra um novo instrumento
     */
    public function store(Request $request): void {
        $usuarioId = $request->getUsuarioId();
        $nome      = trim((string) $request->get('nome', ''));
        $categoria = trim((string) $request->get('categoria', ''));
        $preco     = filter_var($request->get('preco'), FILTER_VALIDATE_FLOAT);
        $estoque   = filter_var($request->get('quantidade_estoque', 0), FILTER_VALIDATE_INT);

        // Validações
        $errors = [];
        if (empty($nome)) {
            $errors['nome'] = 'O nome do instrumento é obrigatório.';
        }
        if (empty($categoria)) {
            $errors['categoria'] = 'A categoria é obrigatória (ex: Cordas, Sopro, Teclas, Percussão).';
        }
        if ($preco === false || $preco < 0) {
            $errors['preco'] = 'O preço deve ser um valor numérico maior ou igual a zero.';
        }
        if ($estoque === false || $estoque < 0) {
            $errors['quantidade_estoque'] = 'A quantidade em estoque deve ser um número inteiro maior ou igual a zero.';
        }

        if (!empty($errors)) {
            Response::error('Dados inválidos para cadastro de instrumento.', 422, $errors);
        }

        $novoId = $this->instrumentoModel->create($usuarioId, $nome, $categoria, (float)$preco, (int)$estoque);
        $instrumentoCriado = $this->instrumentoModel->findByIdAndUsuario($novoId, $usuarioId);

        Response::success($instrumentoCriado, 'Instrumento cadastrado com sucesso!', 201);
    }

    /**
     * PUT /api/instrumentos/{id}
     * Atualiza um instrumento existente
     */
    public function update(Request $request): void {
        $usuarioId = $request->getUsuarioId();
        $id        = (int) $request->getParam('id');

        $instrumento = $this->instrumentoModel->findByIdAndUsuario($id, $usuarioId);
        if (!$instrumento) {
            Response::error('Instrumento não encontrado para atualização.', 404);
        }

        $nome      = trim((string) $request->get('nome', $instrumento['nome']));
        $categoria = trim((string) $request->get('categoria', $instrumento['categoria']));
        $preco     = filter_var($request->get('preco', $instrumento['preco']), FILTER_VALIDATE_FLOAT);
        $estoque   = filter_var($request->get('quantidade_estoque', $instrumento['quantidade_estoque']), FILTER_VALIDATE_INT);

        $errors = [];
        if (empty($nome)) {
            $errors['nome'] = 'O nome não pode ficar vazio.';
        }
        if (empty($categoria)) {
            $errors['categoria'] = 'A categoria não pode ficar vazia.';
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

        $this->instrumentoModel->update($id, $usuarioId, $nome, $categoria, (float)$preco, (int)$estoque);
        $instrumentoAtualizado = $this->instrumentoModel->findByIdAndUsuario($id, $usuarioId);

        Response::success($instrumentoAtualizado, 'Instrumento atualizado com sucesso!');
    }

    /**
     * DELETE /api/instrumentos/{id}
     * Remove um instrumento
     */
    public function destroy(Request $request): void {
        $usuarioId = $request->getUsuarioId();
        $id        = (int) $request->getParam('id');

        $deletado = $this->instrumentoModel->delete($id, $usuarioId);
        if (!$deletado) {
            Response::error('Instrumento não encontrado ou já removido.', 404);
        }

        Response::success(null, 'Instrumento removido com sucesso!');
    }
}
