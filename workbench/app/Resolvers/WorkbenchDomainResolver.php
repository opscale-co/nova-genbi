<?php

declare(strict_types=1);

namespace Workbench\App\Resolvers;

use Opscale\NovaGenbi\Contracts\DomainResolver;

/**
 * Demo schema context for the workbench: a hand-written DBML document for the
 * users table. A real app would describe its full reportable domain here (or
 * generate the DBML from its models).
 */
class WorkbenchDomainResolver implements DomainResolver
{
    public function resolve(): string
    {
        return <<<'DBML'
        Project Workbench {
          Note: 'Demo application used to exercise nova-genbi. The only reportable entity is the user directory.'
        }

        Table users {
          id integer [primary key, note: 'Surrogate key.']
          name varchar [note: 'Full name of the user.']
          email varchar [unique, note: 'Login email.']
          country varchar [note: 'ISO-3166 alpha-2 country code: US, MX, ES, AR, CO, BR.']
          email_verified_at timestamp [note: 'When the email was verified; null if unverified.']
          created_at timestamp [note: 'Registration date.']
          updated_at timestamp [note: 'Last profile update.']

          Note: 'Registered users of the application.'
        }
        DBML;
    }
}
