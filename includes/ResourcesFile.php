<?php

namespace includes;

use includes\BaseMenu;

class ResourcesFile extends BaseMenu
{
    protected $type = 'resources';
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
            </table>
            <div class="notice notice-warning">При загрузке файла, старый будет автоматически удален</div>
            ' . $submit_button_html . '
        </form>';
    }
    protected function generateName(array $data): string
    {
        return 'findex.xlsx';
    }

    public function getUploadedFiles(): string
    {
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM $this->tableName WHERE type = '$this->type'");

        $table = '<table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th>Файл</th>
                <th>Действие</th>
            </tr>
            </thead>
            <tbody>';
        foreach ($results as $result) {
            $table .= '<tr>
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

    public function getRecurseFile()
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->tableName WHERE type = %s ORDER BY date DESC LIMIT 1",$this->type));

    }

}