<?php
/**
 * Tiny server-side validator. Chainable. Stops on first error per field.
 *
 *   $v = (new Validator($_POST))
 *       ->required('email')->email('email')
 *       ->required('name')->max('name', 150);
 *   if ($v->fails()) { flash_errors($v->errors()); ... }
 */
final class Validator
{
    /** @var array<string,string> field => first error */
    private array $errors = [];

    public function __construct(private array $data) {}

    private function value(string $field): ?string
    {
        $v = $this->data[$field] ?? null;
        if (is_array($v)) return null;
        return $v === null ? null : trim((string)$v);
    }

    private function fail(string $field, string $msg): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $msg;
        }
    }

    public function required(string $field, ?string $label = null): self
    {
        $v = $this->value($field);
        if ($v === null || $v === '') {
            $this->fail($field, ($label ?? ucfirst($field)) . ' is required.');
        }
        return $this;
    }

    public function max(string $field, int $len, ?string $label = null): self
    {
        $v = $this->value($field);
        if ($v !== null && mb_strlen($v) > $len) {
            $this->fail($field, ($label ?? ucfirst($field)) . " must be at most {$len} characters.");
        }
        return $this;
    }

    public function min(string $field, int $len, ?string $label = null): self
    {
        $v = $this->value($field);
        if ($v !== null && $v !== '' && mb_strlen($v) < $len) {
            $this->fail($field, ($label ?? ucfirst($field)) . " must be at least {$len} characters.");
        }
        return $this;
    }

    public function email(string $field, ?string $label = null): self
    {
        $v = $this->value($field);
        if ($v !== null && $v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
            $this->fail($field, ($label ?? ucfirst($field)) . ' must be a valid email.');
        }
        return $this;
    }

    public function integer(string $field, ?string $label = null): self
    {
        $v = $this->value($field);
        if ($v !== null && $v !== '' && !preg_match('/^-?\d+$/', $v)) {
            $this->fail($field, ($label ?? ucfirst($field)) . ' must be an integer.');
        }
        return $this;
    }

    public function in(string $field, array $allowed, ?string $label = null): self
    {
        $v = $this->value($field);
        if ($v !== null && $v !== '' && !in_array($v, $allowed, true)) {
            $this->fail($field, ($label ?? ucfirst($field)) . ' is not a valid choice.');
        }
        return $this;
    }

    public function datetime(string $field, ?string $label = null): self
    {
        $v = $this->value($field);
        if ($v !== null && $v !== '' && strtotime($v) === false) {
            $this->fail($field, ($label ?? ucfirst($field)) . ' is not a valid date/time.');
        }
        return $this;
    }

    public function matches(string $field, string $other, ?string $label = null): self
    {
        $a = $this->value($field);
        $b = $this->value($other);
        if ($a !== $b) {
            $this->fail($field, ($label ?? ucfirst($field)) . ' does not match.');
        }
        return $this;
    }

    public function unique(
        string $field,
        string $table,
        string $column,
        ?int $exceptId = null,
        string $idColumn = 'id',
        ?string $label = null
    ): self {
        $v = $this->value($field);
        if ($v === null || $v === '') return $this;
        $sql = "SELECT {$idColumn} AS id FROM {$table} WHERE {$column} = ?";
        $params = [$v];
        if ($exceptId !== null) {
            $sql .= " AND {$idColumn} != ?";
            $params[] = $exceptId;
        }
        $sql .= ' LIMIT 1';
        if (Database::fetch($sql, $params)) {
            $this->fail($field, ($label ?? ucfirst($field)) . ' is already taken.');
        }
        return $this;
    }

    public function addError(string $field, string $msg): self
    {
        $this->fail($field, $msg);
        return $this;
    }

    public function fails(): bool   { return !empty($this->errors); }
    public function passes(): bool  { return empty($this->errors); }
    /** @return array<string,string> */
    public function errors(): array { return $this->errors; }
}
