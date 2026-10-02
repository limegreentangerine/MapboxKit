<?php

namespace Concrete\Package\MapboxKit\Block\Mapbox;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;

class Controller extends BlockController
{
    protected $btTable = 'btMapbox';
    protected $btDefaultSet = 'multimedia';
    protected $btExportTables = [
        'btMapboxMarkers',
    ];
    protected $btInterfaceWidth = 600;
    protected $btInterfaceHeight = 550;
    protected $btCacheBlockOutput = true;
    protected $btCacheBlockOutputOnPost = true;
    protected $btCacheBlockOutputForRegisteredUsers = false;

    protected function getMarkers($form = false)
    {
        $db = $this->app->make('database')->connection();

        $q = 'SELECT * FROM `btMapboxMarkers` mbm WHERE `bID` = ?';
        $v = [
            $this->bID,
        ];

        $rows = $db->fetchAll($q, $v);

        if (count($rows) > 0) {
            if ($form) {
                return $rows;
            }

            $markers = [];

            foreach ($rows as $row) {
                $markers[] = [
                    'latitude' => $row['latitude'],
                    'longitude' => $row['longitude'],
                    'markerColor' => $row['markerColor'],
                ];
            }

            return $markers;
        }

        return false;
    }

    public function getBlockTypeName()
    {
        return t('Mapbox');
    }

    public function getBlockTypeDescription()
    {
        return t('Create mapbox map.');
    }

    public function on_start()
    {
        parent::on_start();

        $html = $this->app->make('helper/html');

        $this->addHeaderItem($html->css('https://api.mapbox.com/mapbox-gl-js/v3.21.0/mapbox-gl.css'));
        $this->addHeaderItem($html->javascript('https://api.mapbox.com/mapbox-gl-js/v3.21.0/mapbox-gl.js'));
    }

    public function getControlPlacementOptions()
    {
        return [
            'top-left' => t('Top Left'),
            'top-right' => t('Top Right'),
            'bottom-left' => t('Bottom Left'),
            'bottom-right' => t('Bottom Right'),
        ];
    }

    public function add()
    {
        $this->set('rows', $this->getMarkers(true));
        $this->set('ch', $this->app->make('helper/form/color'));
    }

    public function edit()
    {
        $this->set('rows', $this->getMarkers(true));
        $this->set('ch', $this->app->make('helper/form/color'));
    }

    public function view()
    {
        $config = [
            'centerLatitude' => $this->centerLatitude,
            'centerLongitude' => $this->centerLongitude,
            'zoom' => $this->zoom,
            'pitch' => $this->pitch,
            'theme' => $this->theme,
            'interactive' => ($this->interactive > 0) ? true : false,
            'show_controls' => ($this->show_controls > 0) ? true : false,
            'control_placement' => $this->control_placement,
            'showBuildings' => ($this->showBuildings > 0) ? true : false,
            'extrusionColor' => $this->extrusionColor,
            'markers' => $this->getMarkers(),
        ];

        $this->set('config', json_encode($config));
    }

    public function save($args)
    {
        $db = $this->app->make('database')->connection();

        // Clear old data for markers
        $q = 'DELETE FROM `btMapboxMarkers` WHERE `bID` = ?';
        $v = [
            $this->bID,
        ];
        $db->executeQuery($q, $v);

        // save primary block info
        parent::save([
            'centerLatitude' => $args['centerLatitude'],
            'centerLongitude' => $args['centerLongitude'],
            'zoom' => $args['zoom'],
            'pitch' => $args['pitch'],
            'theme' => $args['theme'],
            'interactive' => (isset($args['interactive'])) ? 1 : 0,
            'showBuildings' => (isset($args['showBuildings'])) ? 1 : 0,
            'show_controls' => (isset($args['show_controls'])) ? 1 : 0,
            'control_placement' => $args['control_placement'],
            'extrusionColor' => $args['extrusionColor'],
        ]);

        if (array_key_exists('sortOrder', $args)) {
            foreach ($args['sortOrder'] as $k => $v) {
                $markerArgs = [
                    $this->bID,
                    $args['latitude'][$k],
                    $args['longitude'][$k],
                    $args['markerColor'][$k],
                    $args['sortOrder'][$k],
                ];

                $q = 'INSERT INTO `btMapboxMarkers` (`bID`, `latitude`, `longitude`, `markerColor`, `sortOrder`) VALUES (?, ?, ?, ?, ?)';
                $db->executeQuery($q, $markerArgs);
            }
        }
    }

    public function duplicate($newBID)
    {
        $db = $this->app->make('database')->connection();

        $q = 'SELECT * FROM `btMapboxMarkers` WHERE `bID` = ?';
        $v = [
            $this->bID,
        ];
        $rows = $db->fetchAll($q, $v);

        foreach ($rows as $row) {
            $markerArgs = [
                $newBID,
                $row['latitude'],
                $row['longitude'],
                $row['markerColor'],
                $row['sortOrder'],
            ];

            $q = 'INSERT INTO `btMapboxMarkers` (`bID`, `latitude`, `longitude`, `markerColor`, `sortOrder`) VALUES (?, ?, ?, ?, ?)';
            $db->executeQuery($q, $markerArgs);
        }

        parent::duplicate($newBID);
    }

    public function delete()
    {
        $db = $this->app->make('database')->connection();
        $db->executeQuery('DELETE FROM `btMapboxMarkers` WHERE bID = ?', [ $this->bID ]);
        parent::delete();
    }
}
