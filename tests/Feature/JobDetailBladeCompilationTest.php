<?php

namespace Tests\Feature;

use Tests\TestCase;

class JobDetailBladeCompilationTest extends TestCase
{
    public function test_job_detail_compiles_to_valid_php_without_literal_blade_directives(): void
    {
        $source = file_get_contents(resource_path('views/jobs/show.blade.php'));
        $compiled = app('blade.compiler')->compileString($source);

        token_get_all($compiled, TOKEN_PARSE);

        foreach (token_get_all($compiled) as $token) {
            if (is_array($token) && $token[0] === T_INLINE_HTML) {
                $this->assertDoesNotMatchRegularExpression('/@(if|endif|php|endphp)\b|\{\{--/', $token[1]);
            }
        }
    }
}
