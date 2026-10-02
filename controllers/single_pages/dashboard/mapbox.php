<?php

namespace Concrete\Package\MapboxKit\Controller\SinglePage\Dashboard;

use Package;
use UserMessageException;
use Concrete\Core\Page\Controller\DashboardPageController;

class Mapbox extends DashboardPageController
{
    protected $pkg;
    protected $helpers = [
        'form',
    ];

    protected function validate($request)
    {
        $vstrings = $this->app->make('helper/validation/strings');

        if (!$vstrings->notempty($request->request('apiKey'))) {
            $this->error->add(t('Please enter an API Key'), 'apiKey');
        }
    }

    public function on_start()
    {
        parent::on_start();

        $this->pkg = Package::getByHandle('mapbox_kit');
        $this->set('pkg', $this->pkg);
    }

    public function save()
    {
        if ($this->request->isPost()) {
            if (!$this->token->validate('submit')) {
                $this->error->add($this->token->getErrorMessage());
            }

            if (!is_object($this->pkg)) {
                throw new UserMessageException(t('Mapbox package not found'));
            }
            $config = $this->pkg->getFileConfig();


            $this->validate($this->request);
            if (!$this->error->has()) {
                $config->save('mapbox.apiKey', $this->request->request('apiKey'));

                $this->flash('success', t('Mapbox settings saved.'));
                return $this->buildRedirect('/dashboard/mapbox')->send();
            }
            $this->set('formContent', $this->request->request());

        } else {
            return $this->buildRedirect('/dashboard/mapbox')->send();
        }
    }
}
