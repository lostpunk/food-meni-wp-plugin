<?php

namespace includes;

use includes\MenuUploader;

class BaseMenu implements MenuUploader
{
    protected $tableName = '';
    protected $directoryName = 'food';
    protected $path = '';
    protected $type = 'daily';
    protected $subtypes = [
        'sm' => '1-4 параллель',
        'ss' => '5-11 параллель'
    ];

    function __construct($tableName, $directoryName, $path)
    {
        $this->path = $path;
        $this->tableName = $tableName;
        $this->directoryName = $directoryName;
    }


    public function upload(array $data, array $files)
    {
        global $wpdb;
        if (!file_exists($this->path)) {
            mkdir($this->path, 0755, true);
        }
        $date = $data['date'];
        $year = $data['year'] ?? '';
        $subtype = $data['subtype'] ?? '';
        $name = $this->generateName($data);
        if (is_uploaded_file($files["filename"]["tmp_name"])) {
            $file_ext = pathinfo($files["filename"]["name"], PATHINFO_EXTENSION);
            if ($file_ext == "xlsx") {
                $upload_file = $this->path . '/' . $name;
                $existing_file = $this->getUploadedFileByName($name);
                move_uploaded_file($files["filename"]["tmp_name"], $upload_file);
                if ($existing_file) {
                    $wpdb->delete($this->tableName, (array)$existing_file);
                }
                $wpdb->insert(
                    $this->tableName,
                    array(
                        'date' => $date,
                        'year' => $year,
                        'subtype' => $subtype,
                        'type' => $this->type,
                        'filename' => $name,
                        'url' => site_url($this->directoryName . '/' . $name)
                    )
                );
                wp_redirect(add_query_arg('success', 'Файл успешно загружен'));
                exit;
            } else {
                echo '<div class="error notice"><p>Неверный тип файла! Разрешены только .xlsx файлы.</p></div>';
            }
        }
    }

    protected function generateName(array $data): string
    {
        $date = $data['date'];
        $subtype = $data['subtype'] ?? '';
        return $date . '-' . $subtype . '.xlsx';
    }

    public function getUploadedFileByName(string $fileName)
    {
        global $wpdb;
        return $wpdb->get_row("SELECT * FROM $this->tableName WHERE type = '$this->type' AND filename = '$fileName'");
    }

    public function deleteFile(string $filename): bool
    {
        global $wpdb;
        $file = $this->getUploadedFileByName($filename);
        if ($file) {
            $file_path = $this->path . '/' . $file->filename;

            if (file_exists($file_path)) {
                unlink($file_path);
            }
            $wpdb->delete($this->tableName, (array)$file);
            wp_redirect(add_query_arg('success', 'Файл успешно удален'));
            exit;
        } else {
            return false;
        }
    }


    public function getUploadForm(): string
    {
        ob_start();
        submit_button('Загрузить меню', 'primary', 'upload');
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
                    <th scope="row">Дата меню</th>
                    <td><input type="date" name="date" class="regular-text" required value="' . date('Y-m-d') . '"></td>
                </tr>
            </table>
            <div class="notice notice-warning">При загрузке файла на дату у которой уже есть файл, старый будет автоматически удален</div>
            ' . $submit_button_html . '
        </form>';
    }

    protected function getSubtypeSelectHtml(): string
    {
        $options = '<select name="subtype" class="regular-text" required><option value="">Выберите параллель</option>';
        foreach ($this->subtypes as $key => $subtype) {
            $options .= '<option value="' . $key . '">' . $subtype . '</option>';
        }
        $options .= '</select>';
        return $options;
    }


    public function getTodayMenuTable(): string
    {
        global $wpdb;
        $today = date("Y-m-d");
        $results = $wpdb->get_results("SELECT * FROM $this->tableName WHERE type = '$this->type' AND date = '$today' AND subtype IN ('sm','ss')");
        $files = [];
        if ($results) {
            foreach ($results as $result) {
                $files[$result->subtype] = $result;
            }
        }
        $table = '<table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th>Начальная школа <span>[today_menu_sm]</span></th>
                <th>Основная, средняя школа <span>[today_menu_ss]</span></th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>';

        if (isset($files['sm'])) {
            $table .= '<span class="text-success">Файл загружен</span><br>
                        <p><a href="' . $files['sm']->url . '" target="_blank">' . $files['sm']->filename . '</a></p>
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
                        <p><a href="' . $files['ss']->url . '" target="_blank">' . $files['ss']->filename . '</a></p>
                        <form method="POST" style="display:inline-block; margin-left:20px;">
                         <input type="hidden" name="name" value="' . $files['ss']->filename . '">
                        <button type="submit" name="delete" class="button button-secondary">Удалить</button>
                        </form>';
        } else {
            $table .= '<span class="text-danger">Файл не загружен</span>';
        }
        $table .= '</td>
            </tr>
            </tbody>
        </table>';
        return $table;
    }

    public function getUploadedFiles()
    {
        global $wpdb;
        $files = $wpdb->get_results("SELECT * FROM $this->tableName WHERE type = '$this->type' ORDER BY date DESC");
        $years = [];

        foreach ($files as $file) {
            $year = date('Y', strtotime($file->date));
            $month = date('m', strtotime($file->date));
            $day = date('d', strtotime($file->date));

            if (!isset($years[$year])) {
                $years[$year] = [];
            }

//            if (!isset($years[$year][$month])) {
//                $years[$year][$month] = [];
//            }

            $years[$year][$month][$day][$file->subtype] = $file;
        }
        $html = '<div class="years-btns">';
        foreach ($years as $yearKey => $months) {
            $html .= '<div class="year-btn btn" data-year="' . $yearKey . '">' . $yearKey . '</div>';
        }
        $html .= '</div>';
        //   dd($months);

        $html .= '<div class="global-container">';
        foreach ($years as $yearKey => $months) {
            $html .= '<div class="year-container" data-year="' . $yearKey . '">';
            $html .= '<div class="month-btns">';
            foreach ($months as $mounthNumber => $files) {
                $html .= '<div class="month-btn btn" data-year="' . $yearKey . '" data-month="' . $mounthNumber . '">' . $this->getMonthByNumber($mounthNumber) . '</div>';
            }
            $html .= '</div>';

            $html .= '<div class="month-container">';
            foreach ($months as $mounthNumber => $files) {
                $days = cal_days_in_month(CAL_GREGORIAN, $mounthNumber, $yearKey);
                $html .= '<div class="month-item" data-year="' . $yearKey . '" data-month="' . $mounthNumber . '">';
                $html .= '<table class="wp-list-table widefat fixed striped">
                    <thead>
                    <th>День</th>
                    <th>Начальная школа</th>
                    <th>Основная, средняя школа</th>
                </thead>
                <tbody>';
                for ($i = 1; $i <= $days; $i++) {
                    $formattedNumber = str_pad($i, 2, '0', STR_PAD_LEFT);
                    $smFile = $files[$formattedNumber]['sm'] ?? [];
                    $ssFile = $files[$formattedNumber]['ss'] ?? [];
                    $smHtml = $smFile ? '<a href="' . $smFile->url . '">' . $smFile->filename . '</a>
                        <form method="POST" style="display:inline-block; margin-left:20px;">
                         <input type="hidden" name="name" value="' . $smFile->filename . '">
                        <button type="submit" name="delete" class="button button-secondary">Удалить</button>
                        </form>'
                        : '<span>Файл не загружен</span>';
                    $ssHtml = $ssFile ? '<a href="' . $ssFile->url . '">' . $ssFile->filename . '</a>
                            <form method="POST" style="display:inline-block; margin-left:20px;">
                         <input type="hidden" name="name" value="' . $ssFile->filename . '">
                        <button type="submit" name="delete" class="button button-secondary">Удалить</button>
                        </form>'
                        : '<span>Файл не загружен</span>';
                    $html .= '<tr>
                        <td>' . $i . '</td>
                        <td>' . $smHtml . '</td>
                        <td>' . $ssHtml . '</td>
                        </tr>';
                }
                $html .= '</tbody>
                </table>';
                $html .= '</div>';
            }
            $html .= '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    protected function getMonthByNumber($number)
    {
        switch ($number) {
            case '01':
                return 'Январь';
            case '02':
                return 'Февраль';
            case '03':
                return 'Март';
            case '04':
                return 'Апрель';
            case '05':
                return 'Май';
            case '06':
                return 'Июнь';
            case '07':
                return 'Июль';
            case '08':
                return 'Август';
            case '09':
                return 'Сентябрь';
            case '10':
                return 'Октябрь';
            case '11':
                return 'Ноябрь';
            case '12':
                return 'Декабрь';
            default:
                return 'Неизвестно';
        }
    }

    public function getTodayMenu($subtype)
    {
        global $wpdb;
        $today = wp_date("Y-m-d");
        $menu = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $this->tableName WHERE type = %s AND date = %s AND subtype = %s",
                $this->type,
                $today,
                $subtype
            )
        );
        return $menu;

    }

    public function getLimitedMenuList($subtype, $limit = 30)
    {
        global $wpdb;
        $menuList = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $this->tableName WHERE type = %s AND subtype = %s ORDER BY date DESC LIMIT %d",
                $this->type,
                $subtype,
                $limit
            )
        );
        return $menuList;
    }
}