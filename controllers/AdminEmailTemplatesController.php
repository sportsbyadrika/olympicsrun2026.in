<?php
final class AdminEmailTemplatesController
{
    public function index(): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        render('admin/email_templates/index', [
            'title'     => 'Email Templates — Admin',
            'templates' => EmailTemplate::all(),
        ]);
    }

    public function edit(string $key): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        $tpl = EmailTemplate::find($key);
        if (!$tpl) { http_response_code(404); render('errors/404'); return; }
        render('admin/email_templates/form', [
            'title'    => 'Edit template — ' . $tpl['name'],
            'template' => $tpl,
        ]);
    }

    public function update(string $key): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        Csrf::requireValidPost();
        $tpl = EmailTemplate::find($key);
        if (!$tpl) { http_response_code(404); render('errors/404'); return; }

        $v = (new Validator($_POST))
            ->required('name',      'Name')->max('name', 150)
            ->required('subject',   'Subject')->max('subject', 255)
            ->required('body_html', 'HTML body')
            ->max('placeholders', 500);
        if ($v->fails()) {
            flash_errors($v->errors());
            flash_old($_POST);
            redirect('/admin/email-templates/' . urlencode($key) . '/edit');
        }

        EmailTemplate::update($key, $_POST, Auth::id());
        flash_set('success', 'Template saved.');
        redirect('/admin/email-templates');
    }
}
