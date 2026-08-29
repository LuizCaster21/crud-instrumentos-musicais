<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Amplificador;

class AmplificadorController {
    private Amplificador $amplificadorModel;

    public function __construct() {
        $this->amplificadorModel = new Amplificador();
    }

    /**
     * GET /api/amplificadores
     * Lista todos os amplificadores do usuário autenticado
     */
    public function index(Request $request): void {
        $usuarioId = $request->getUsuarioId();
        $amplificadores = $this->amplificadorModel->findAllByUsuario($usuarioId);

        Response::success($amplificadores, 'Amplificadores recuperados com sucesso!');
    }

    /**
     * GET /api/amplificadores/{id}
     * Busca um amplificador específico
     */
    public function show(Request $request): void {
        $usuarioId = $request->getUsuarioId();
        $id = (int) $request->getParam('id');

        $amplificador = $this->amplificadorModel->findByIdAndUsuario($id, $usuarioId);
        if (!$amplificador) {
            Response::error('Amplificador não encontrado.', 404);
        }

        Response::success($amplificador, 'Amplificador encontrado com sucesso!');
    }

    /**
     * POST /api/amplificadores
     * Cadastra um novo amplificador
     */
    public function store(Request $request): void {
        $usuarioId      = $request->getUsuarioId();
        $marca          = trim((string) $request->get('marca', ''));
        $modelo         = trim((string) $request->get('modelo', ''));
        $tipo           = trim((string) $request->get('tipo', ''));
        $potenciaWatts  = filter_var($request->get('potencia_watts'), FILTER_VALIDATE_INT);
        $preco          = filter_var($request->get('preco'), FILTER_VALIDATE_FLOAT);
        $estoque        = filter_var($request->get('quantidade_estoque', 0), FILTER_VALIDATE_INT);

        $errors = [];
        if (empty($marca)) {
            $errors['marca'] = 'A marca é obrigatória.';
        }
        if (empty($modelo)) {
            $errors['modelo'] = 'O modelo é obrigatório.';
        }
        if (empty($tipo)) {
            $errors['tipo'] = 'O tipo é obrigatório (ex: Valvulado, Transistorizado, Híbrido, Digital).';
        }
        if ($potenciaWatts === false || $potenciaWatts < 0) {
            $errors['potencia_watts'] = 'A potência em Watts deve ser um número inteiro positivo.';
        }
        if ($preco === false || $preco < 0) {
            $errors['preco'] = 'O preço deve ser um valor numérico válido.';
        }
        if ($estoque === false || $estoque < 0) {
            $errors['quantidade_estoque'] = 'O estoque deve ser um número inteiro maior ou igual a zero.';
        }

        if (!empty($errors)) {
            Response::error('Dados inválidos para cadastro de amplificador.', 422, $errors);
        }

        $novoId = $this->amplificadorModel->create(
            $usuarioId,
            $marca,
            $modelo,
            $tipo,
            (int)$potenciaWatts,
            (float)$preco,
            (int)$estoque
        );

        $amplificadorCriado = $this->amplificadorModel->findByIdAndUsuario($novoId, $usuarioId);
        Response::success($amplificadorCriado, 'Amplificador cadastrado com sucesso!', 201);
    }

    /**
     * PUT /api/amplificadores/{id}
     * Atualiza um amplificador existente
     */
    public function update(Request $request): void {
        $usuarioId = $request->getUsuarioId();
        $id        = (int) $request->getParam('id');

        $amplificador = $this->amplificadorModel->findByIdAndUsuario($id, $usuarioId);
        if (!$amplificador) {
            Response::error('Amplificador não encontrado para atualização.', 404);
        }

        $marca         = trim((string) $request->get('marca', $amplificador['marca']));
        $modelo        = trim((string) $request->get('modelo', $amplificador['modelo']));
        $tipo          = trim((string) $request->get('tipo', $amplificador['tipo']));
        $potenciaWatts = filter_var($request->get('potencia_watts', $amplificador['potencia_watts']), FILTER_VALIDATE_INT);
        $preco         = filter_var($request->get('preco', $amplificador['preco']), FILTER_VALIDATE_FLOAT);
        $estoque       = filter_var($request->get('quantidade_estoque', $amplificador['quantidade_estoque']), FILTER_VALIDATE_INT);

        $errors = [];
        if (empty($marca)) {
            $errors['marca'] = 'A marca não pode ficar vazia.';
        }
        if (empty($modelo)) {
            $errors['modelo'] = 'O modelo não pode ficar vazio.';
        }
        if (empty($tipo)) {
            $errors['tipo'] = 'O tipo não pode ficar vazio.';
        }
        if ($potenciaWatts === false || $potenciaWatts < 0) {
            $errors['potencia_watts'] = 'A potência deve ser válida e maior ou igual a zero.';
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

        $this->amplificadorModel->update(
            $id,
            $usuarioId,
            $marca,
            $modelo,
            $tipo,
            (int)$potenciaWatts,
            (float)$preco,
            (int)$estoque
        );

        $amplificadorAtualizado = $this->amplificadorModel->findByIdAndUsuario($id, $usuarioId);
        Response::success($amplificadorAtualizado, 'Amplificador atualizado com sucesso!');
    }

    /**
     * DELETE /api/amplificadores/{id}
     * Remove um amplificador
     */
    public function destroy(Request $request): void {
        $usuarioId = $request->getUsuarioId();
        $id        = (int) $request->getParam('id');

        $deletado = $this->amplificadorModel->delete($id, $usuarioId);
        if (!$deletado) {
            Response::error('Amplificador não encontrado ou já removido.', 404);
        }

        Response::success(null, 'Amplificador removido com sucesso!');
    }
}
