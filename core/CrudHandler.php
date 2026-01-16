<?php
/**
 * CRUD Handler Universal
 * Sistema genérico para operações CRUD em qualquer módulo
 */

class CrudHandler {
    private $pdo;
    private $config;
    private $userId;

    public function __construct($pdo, $config, $userId = null) {
        $this->pdo = $pdo;
        $this->config = $config;
        $this->userId = $userId;
    }

    /**
     * Salvar (criar ou atualizar) registro
     */
    public function save($data) {
        $id = isset($data['id']) ? intval($data['id']) : 0;
        $isEdit = $id > 0;

        // Validar campos obrigatórios
        foreach ($this->config['fields'] as $fieldName => $fieldConfig) {
            if (isset($fieldConfig['required']) && $fieldConfig['required']) {
                if (empty($data[$fieldName]) && $data[$fieldName] !== '0') {
                    if (!$isEdit || isset($data[$fieldName])) {
                        throw new Exception("Campo '{$fieldName}' é obrigatório");
                    }
                }
            }
        }

        // Preparar dados
        $fields = [];
        $values = [];

        foreach ($this->config['fields'] as $fieldName => $fieldConfig) {
            if (isset($data[$fieldName])) {
                $value = $data[$fieldName];

                // Aplicar tipo
                if (isset($fieldConfig['type'])) {
                    switch ($fieldConfig['type']) {
                        case 'int':
                            $value = intval($value);
                            break;
                        case 'float':
                            $value = floatval($value);
                            break;
                        case 'string':
                        case 'text':
                            $value = trim($value);
                            break;
                    }
                }

                $fields[$fieldName] = $value;
            } elseif (!$isEdit && isset($fieldConfig['default'])) {
                $fields[$fieldName] = $fieldConfig['default'];
            }
        }

        // Adicionar ownership field se configurado
        if (isset($this->config['ownership_field']) && !$isEdit) {
            $fields[$this->config['ownership_field']] = $this->userId;
        }

        if ($isEdit) {
            // Atualizar
            return $this->update($id, $fields);
        } else {
            // Criar
            return $this->create($fields);
        }
    }

    /**
     * Criar novo registro
     */
    private function create($fields) {
        $fieldNames = array_keys($fields);
        $placeholders = array_fill(0, count($fieldNames), '?');

        $sql = "INSERT INTO {$this->config['table']} ("
             . implode(', ', $fieldNames)
             . ") VALUES ("
             . implode(', ', $placeholders)
             . ")";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($fields));

        $insertId = $this->pdo->lastInsertId();

        // Executar callback pós-criação se existir
        if (isset($this->config['on_create']) && is_callable($this->config['on_create'])) {
            $this->config['on_create']($this->pdo, $insertId, $this->userId);
        }

        return $insertId;
    }

    /**
     * Atualizar registro existente
     */
    private function update($id, $fields) {
        // Verificar ownership se configurado
        if (isset($this->config['ownership_field'])) {
            $checkSql = "SELECT {$this->config['primary_key']} FROM {$this->config['table']}
                        WHERE {$this->config['primary_key']} = ?
                        AND {$this->config['ownership_field']} = ?";
            $stmt = $this->pdo->prepare($checkSql);
            $stmt->execute([$id, $this->userId]);

            if (!$stmt->fetch()) {
                throw new Exception("Registro não encontrado ou sem permissão");
            }
        }

        $setParts = [];
        $values = [];

        foreach ($fields as $fieldName => $value) {
            $setParts[] = "$fieldName = ?";
            $values[] = $value;
        }

        $values[] = $id;
        if (isset($this->config['ownership_field'])) {
            $values[] = $this->userId;
        }

        $sql = "UPDATE {$this->config['table']} SET "
             . implode(', ', $setParts)
             . " WHERE {$this->config['primary_key']} = ?";

        if (isset($this->config['ownership_field'])) {
            $sql .= " AND {$this->config['ownership_field']} = ?";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        // Executar callback pós-atualização se existir
        if (isset($this->config['on_update']) && is_callable($this->config['on_update'])) {
            $this->config['on_update']($this->pdo, $id, $this->userId);
        }

        return $id;
    }

    /**
     * Deletar registro
     */
    public function delete($id) {
        // Verificar ownership se configurado
        if (isset($this->config['ownership_field'])) {
            $checkSql = "SELECT {$this->config['primary_key']} FROM {$this->config['table']}
                        WHERE {$this->config['primary_key']} = ?
                        AND {$this->config['ownership_field']} = ?";
            $stmt = $this->pdo->prepare($checkSql);
            $stmt->execute([$id, $this->userId]);

            if (!$stmt->fetch()) {
                throw new Exception("Registro não encontrado ou sem permissão");
            }
        }

        // Executar callback pré-deleção se existir
        if (isset($this->config['on_delete']) && is_callable($this->config['on_delete'])) {
            $this->config['on_delete']($this->pdo, $id, $this->userId);
        }

        $sql = "DELETE FROM {$this->config['table']} WHERE {$this->config['primary_key']} = ?";

        if (isset($this->config['ownership_field'])) {
            $sql .= " AND {$this->config['ownership_field']} = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id, $this->userId]);
        } else {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
        }

        return true;
    }

    /**
     * Buscar um registro
     */
    public function get($id) {
        $sql = "SELECT * FROM {$this->config['table']} WHERE {$this->config['primary_key']} = ?";

        if (isset($this->config['ownership_field'])) {
            $sql .= " AND {$this->config['ownership_field']} = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id, $this->userId]);
        } else {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
        }

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            throw new Exception("Registro não encontrado");
        }

        return $result;
    }

    /**
     * Listar registros
     */
    public function list($filters = [], $orderBy = null, $limit = null) {
        $sql = "SELECT * FROM {$this->config['table']}";
        $where = [];
        $values = [];

        // Adicionar ownership filter se configurado
        if (isset($this->config['ownership_field'])) {
            $where[] = "{$this->config['ownership_field']} = ?";
            $values[] = $this->userId;
        }

        // Adicionar filtros adicionais
        foreach ($filters as $field => $value) {
            $where[] = "$field = ?";
            $values[] = $value;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }

        if ($limit) {
            $sql .= " LIMIT $limit";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
