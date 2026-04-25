<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeRepositoryModule extends Command
{
    protected $signature = 'make:repo {name}';

    protected $description = 'Create repository structure inside app/Repository/{Name}';

    public function handle()
    {
        $name = Str::studly($this->argument('name'));

        $basePath = app_path("Repository/{$name}");
        $eloquentPath = "{$basePath}/Eloquent";

        $repositoryFile = "{$basePath}/{$name}Repository.php";
        $eloquentFile = "{$eloquentPath}/{$name}Eloquent.php";

        if (! File::exists($basePath)) {
            File::makeDirectory($basePath, 0755, true);
        }

        if (! File::exists($eloquentPath)) {
            File::makeDirectory($eloquentPath, 0755, true);
        }

        if (! File::exists($repositoryFile)) {
            File::put($repositoryFile, "<?php

namespace App\Repository\\{$name};

interface {$name}Repository
{
    public function all();
    public function find(\$id);
    public function create(array \$data);
    public function update(\$id, array \$data);
    public function delete(\$id);
}
");
        }

        if (! File::exists($eloquentFile)) {
            File::put($eloquentFile, "<?php

namespace App\Repository\\{$name}\Eloquent;

use App\Models\\{$name};
use App\Repository\\{$name}\\{$name}Repository;

class {$name}Eloquent implements {$name}Repository
{
    protected \$model;

    public function __construct({$name} \$model)
    {
        \$this->model = \$model;
    }

    public function all()
    {
        return \$this->model->all();
    }

    public function find(\$id)
    {
        return \$this->model->findOrFail(\$id);
    }

    public function create(array \$data)
    {
        return \$this->model->create(\$data);
    }

    public function update(\$id, array \$data)
    {
        \$record = \$this->find(\$id);
        \$record->update(\$data);
        return \$record;
    }

    public function delete(\$id)
    {
        return \$this->find(\$id)->delete();
    }
}
");
        }

        $this->info("Repository structure for {$name} created successfully ✔️");
    }
}
