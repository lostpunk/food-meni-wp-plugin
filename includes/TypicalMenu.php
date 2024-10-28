<?php

namespace includes;

use includes\BaseMenu;

class TypicalMenu extends BaseMenu
{
    protected $type = 'typical';

    public function __construct($tableName, $directoryName, $path)
    {
        parent::__construct($tableName, $directoryName, $path);
    }


    public function getUploadForm(): string
    {
        ob_start();
        submit_button('Загрузить', 'primary', 'upload');
        $submit_button_html = ob_get_clean();
        return '<form method="POST" enctype="multipart/form-data">
            
            <table class="form-table">
            
                <tr>
                    <th scope="row">Выберите файл</th>
                    <td><input type="file" name="filename" class="regular-text" required accept=".xlsx"></td>
                </tr>
                <tr>
                    <th scope="row">Выберите параллель</th>
                    <td>
                        ' . $this->getSubtypeSelectHtml() . '
                    </td>
                </tr>
                <tr >
                    <th scope="row">Год</th>
                    <td><input type="number" name="year" class="regular-text" step="1" required value="' . date('Y') . '"></td>
                </tr>
            </table>
            <div class="notice notice-warning">При загрузке файла на дату у которой уже есть файл, старый будет автоматически удален</div>
            ' . $submit_button_html . '
        </form>';
    }

    protected function generateName(array $data): string
    {
        $year = $data['year'] ?? '';
        $subtype = $data['subtype'] ?? '';
        return 'tm' . $year . '-' . $subtype . '.xlsx';
    }

    public function getUploadedFiles(): string
    {
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM $this->tableName WHERE type = '$this->type'");

        $table = '<table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th>Год</th>
                <th>Файл</th>
                <th>Действие</th>
            </tr>
            </thead>
            <tbody>';
        foreach ($results as $result) {
            $table .= '<tr>
                        <td>' . $result->year . '</td>
                        <td><a href="' . $result->url . '">' . $result->filename . '</a></td>
                        <td><form method="POST" style="display:inline-block; margin-left:20px;">
                        <input type="hidden" name="name" value="' . $result->filename . '">
                        <button type="submit" name="delete" class="button button-secondary">Удалить</button>
                        </form></td>
                        </tr>';
        }
        $table .= ' </tbody>
        </table>';
        return $table;
    }

    public function getTypicalMenuTable(): string
    {
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM $this->tableName WHERE type = '$this->type' AND subtype IN ('sm','ss')");
        $groupedFiles = [];
        if ($results) {
            foreach ($results as $result) {
                $groupedFiles[$result->year][$result->subtype] = $result;
            }
        }

        $table = '<table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th>Год</th>
                <th>Начальная школа</th>
                <th>Основная, средняя школа</th>
            </tr>
            </thead>
            <tbody>';
        foreach ($groupedFiles as $year => $files) {
            $table .= '<tr><td>' . $year . '</td><td>';
            if (isset($files['sm'])) {
                $table .= '<span class="text-success">Файл загружен</span><br>
                        <a href="' . $files['sm']->url . '" target="_blank">' . $files['sm']->filename . '</a>
                        <form method="POST" style="display:inline-block; margin-left:20px;">
                        <input type="hidden" name="name" value="' . $files['sm']->filename . '">
                        <button type="submit" name="delete" class="button button-secondary">Удалить</button>
                        </form>';
            } else {
                $table .= '<span class="text-danger">Файл не загружен</span>';
            }
            $table .= '</td><td>';
            if (isset($files['ss'])) {
                $table .= '<span class="text-success">Файл загружен</span><br>
                        <a href="' . $files['ss']->url . '" target="_blank">' . $files['ss']->filename . '</a>
                        <form method="POST" style="display:inline-block; margin-left:20px;">
                         <input type="hidden" name="name" value="' . $files['ss']->filename . '">
                        <button type="submit" name="delete" class="button button-secondary">Удалить</button>
                        </form>';
            } else {
                $table .= '<span class="text-danger">Файл не загружен</span>';
            }
            $table .= '</td> </tr>';
        }
        $table .= '</tbody>
            </table>';
        return $table;
    }

    public function getLastTypicalMenu($subtype)
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->tableName WHERE type = %s AND subtype = %s ORDER by year DESC", $this->type, $subtype));
    }


}