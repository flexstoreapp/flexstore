<?php

declare(strict_types=1);

namespace App\Http\Requests\Installer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class StoreDatabaseRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isSqlite = $this->input('db_connection') === 'sqlite';

        return [
            'db_connection' => ['required', 'string', Rule::in(['mysql', 'pgsql', 'sqlite'])],
            'db_host' => [$isSqlite ? 'nullable' : 'required', 'string', 'max:253'],
            'db_port' => [$isSqlite ? 'nullable' : 'required', 'integer', 'between:1,65535'],
            'db_database' => ['required', 'string', 'max:128', ...($isSqlite ? ['regex:/^[\w\-.]+$/'] : [])],
            'db_username' => [$isSqlite ? 'nullable' : 'required', 'string', 'max:128'],
            'db_password' => ['nullable', 'string', 'max:256'],
            'demo_data' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'db_database.regex' => 'The database name must be a filename without any path (e.g., database.sqlite).',
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'db_connection' => 'database driver',
            'db_host' => 'database host',
            'db_port' => 'database port',
            'db_database' => 'database name',
            'db_username' => 'database username',
            'db_password' => 'database password',
            'demo_data' => 'demo data',
        ];
    }
}
