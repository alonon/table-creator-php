<?php
//hash algoritmalarını seçebilsin md5 vs
mysql_query("SET NAMES 'utf8'");

class TableEditor {

    private $fields = array();
    private $displayFilter;
    private $primaryKey;
    private $table;
    private $db;
    private $systemErrors = array();
    private $errors = array();
    private $where = "where 1=1";

    function TableEditor($db, $table) {
        // Check the db resource
        if (!is_resource($db))
            die("First argument is not a valid database connection!");
        $this->db = $db;
        $this->table = $table;
        $this->field;
        $query = mysql_query("select * from user");
        //print_r(mysql_fetch_row($query));
    }

    function __construct() {
        $args = func_get_args ();
        call_user_func_array(array(&$this, 'TableEditor'), $args);
    }

    function setConfig($name, $value) {
        $this->config [$name] = $value;
    }

    function addSystemError($error) {
        $this->systemErrors [] = $error;
    }

    function addError($error) {
        $this->errors [] = $error;
    }

    function displayError($arr = array()) {
        foreach ($arr as $id => $value)
            echo "$value <br />";
    }

    function getConfig($name) {
        return $this->config[$name];
    }

    function setDisplayFilter($condition, $operation) {
        if ($condition != "" && $operation != "")
            $this->where .= " $operation $condition";
    }

    function dbQuery($sql) {
        $resource = mysql_query($sql) or die(mysql_error());
        if (is_resource($resource) || $resource == True)
            return $resource;
        else
            $this->addSystemError("Invalid sql in dbQuery: <strong>$sql</strong>");
        return false;
    }

    function getQueryTablesAndFields() {
        //		echo "<h3>getQueryTablesAndFields'e girerkenki dizi</h3><pre>";
        //		print_r ( $this->fields );
        //		echo "<pre/>";
        $table = $this->table;
        $fieldsKey = array_keys($this->fields);
        foreach ($this->fields as $fieldName => $fieldsConfigArray) {
            //			echo "<h3>getQueryTablesAndFields()'teki forEach içindeki bilgiler</h3><pre>";
            //			echo "fieldName=$fieldName<br />";
            //			print_r ( $this->fields );
            //			echo "<pre/>";
            if ($this->fields [$fieldName] ['display'] === True || $_GET['process'] != "") {
                if ($this->fields [$fieldName] ['foreign_key'] === True) {
                    //				echo "Foreign key olan fieldlar $fieldName";
                    if (!$this->fields [$fieldName] ['foreign_table'] && $this->fields [$fieldName] ['foreign_field']) {
                        $this->addSystemError('You have to define foreign_table and foreign_field for field: ' . $this->fields [$fieldName]);
                    } else {
                        //echo "".print_r($this->fields [$fieldName])."";
                        //echo "<br>.budur".$fieldName."=".$this->fields [$fieldName]['foreign_table']."<br>";
                        $table .= ', ' . $this->fields [$fieldName] ['foreign_table'];
                        $fields .= "," . $this->fields [$fieldName] ['foreign_table'] . "." . $this->fields [$fieldName] ['foreign_displayField'] . " as $fieldName ";
                        //Burda birden fazla join olursa sorun çıkabilir bir bak buna.
                        $joinClause = $this->fields [$fieldName] ['foreign_table'] . '.' . $this->fields [$fieldName] ['foreign_field'] . '=' . $this->table . '.' . $fieldName;
                        $this->setDisplayFilter($joinClause, "and");
                        //echo "<br>$joinClause<br/>";
                    }
                } else {
                    $fields .= ",$fieldName ";
                }
            }
        }
        return array(substr($fields, 1, - 1), $table);
    }

    function setNames($fieldsDisplayName) {
        if (!empty($fieldsDisplayName) and is_array($fieldsDisplayName)) {
            foreach ($fieldsDisplayName as $fieldName => $displayName) {
                if (empty($this->fields [$fieldName])) {
                    $this->fields [$fieldName] ['displayName'] = $displayName;
                    $this->fields [$fieldName] ['display'] = True;
                    $this->fields [$fieldName] ['primary_key'] = False;
                    $this->fields [$fieldName] ['searchable'] = True;
                    $this->fields [$fieldName] ['sortable'] = True;
                    $this->fields [$fieldName] ['default_value'] = False;
                    $this->fields [$fieldName] ['allowInsert'] = True;
                    $this->fields [$fieldName] ['required'] = False;
                    $this->fields [$fieldName] ['input_type'] = 'text';
                    $this->fields [$fieldName] ['foreign_key'] = False;
                    $this->fields [$fieldName] ['foreign_table'] = False;
                    $this->fields [$fieldName] ['foreign_field'] = False;
                    $this->fields [$fieldName] ['foreign_displayField'] = False;
                    $this->fields [$fieldName] ['functionName'] = False;
                }
            }
        }
        //			echo "<h3>setNames'ten sonraki dizi</h3><pre>";
        //			print_r ( $this->fields );
        //			echo "<pre/>";
    }

    function setPrimaryKey($fieldName) {
        $this->primaryKey = $fieldName;
    }

    function setFieldsConfig($fieldsConfig) {
        if (!empty($fieldsConfig) and is_array($fieldsConfig)) {
            foreach ($fieldsConfig as $fieldName => $fieldsConfig) {
                if (array_key_exists($fieldName, $this->fields)) {
                    foreach ($fieldsConfig as $configKey => $configValue) {
                        if ($this->fields [$fieldName] [$configKey] !== $configValue && array_key_exists($configKey, $this->fields [$fieldName])) {
                            $this->fields [$fieldName] [$configKey] = $configValue;
                        }
                    }
                }
                else
                    $this->addSystemError("Invalid field for configuration: $fieldName");
            }
        }
        //		echo "<h3>setNames'ten sonraki dizi</h3><pre>";
        //		print_r ( $this->fields );
        //		echo "<pre/>";
    }

    function addDisplayCallback($fieldName, $callback) {
        if (is_callable($callback))
            $this->fields [$fieldName] ['DisplayCallBack'] = $callback;
        else
            $this->addSystemError("Failed to add display callback - not a valid PHP callback for $fieldName");
    }

    function setDefaultPerPage($perPage = "10") {
        $this->defaultPerPage = $perPage;
    }

    function setOrderBy($orderBy = "", $type = "desc") {
        if ($orderBy != "")
            return "order by $orderBy $type";
        else
            return "order by $this->primaryKey $type";
    }

    function getDisplayedFieldName() {
        $fieldDisplayName = array();
        foreach ($this->fields as $fieldName => $fieldsConfig)
            if ($this->fields [$fieldName] ['display'])
                $fieldDisplayName [] = $fieldName;
        return $fieldDisplayName;
    }

    function controlFieldName($key) {
        foreach ($this->fields as $fieldName => $value) {
            if ($value ['foreign_field'] === $key) {
                return $fieldName;
            }
        }
    }

    function applyFilter(&$filter) {
        foreach ($filter as $k => $row) {
            foreach ($row as $fieldName => $value)
                if (is_array($this->fields [$fieldName]) && $this->fields [$fieldName] ['default_value'] !== False)
                    $filter [$k] [$fieldName] = $this->fields ['fieldName'] ['value'];
        }
    }

    function getTableResult($sql) {
        $results = array();
        $resource = $this->dbQuery($sql);
        while ($row = mysql_fetch_assoc($resource)) {
            $results [] = $row;
        }
        $this->applyFilter($results);
        return $results;
    }

    function getRowResult($sql) {
        $resource = $this->dbQuery($sql);
        return @mysql_fetch_assoc($resource);
    }

    function deleteRow($value) {
        if ($this->dbQuery("delete from $this->table where $this->primaryKey = '$value'")

            );
        return True;
    }

    function displayRow($value) {
        list($fields, $table) = $this->getQueryTablesAndFields();
        $this->setDisplayFilter($joinClause, "and");
        $this->setDisplayFilter("$this->primaryKey = $value", "and");


        //echo $where;
        $sql = "select $fields from $table $this->where";
        $displayedNames = $this->getDisplayedFieldName();
        $results = $this->getRowResult($sql);
?>
    <table class="tableCreator">
<? foreach ($results as $fieldName => $value) : ?>
            <tr>
                <td style="width:200px;"><?=$this->fields [$fieldName] ['displayName'] ?></td>
                <td style="width:200px;">

        		<?=$value ?>


        </td>
    </tr>
<? endforeach ?>
            </table>
<?php
        }

        function displayTable($page = "") {
            $searchClause = "";
            $sortType = $_GET['sortType'];
            $sortBy = $_GET['sortBy'];
            if ($page < 1)
                $page = 1;
            $after = ($page * $this->defaultPerPage) - $this->defaultPerPage;
            list ( $fields, $table) = $this->getQueryTablesAndFields();
            if ($sortType == '')
                $sortType = 'desc';
            else if ($sortType == 'desc')
                $sortType = 'asc';
            else
                $sortType = 'desc';
            $orderBy = $this->setOrderBy($sortBy, $sortType);

            $sql = "select $fields from $table $this->where $orderBy limit $after,$this->defaultPerPage";
            //echo "<h3>$sql</h3>";
            $results = $this->getTableResult($sql);
            $total = @mysql_fetch_assoc(mysql_query("select count($this->primaryKey) from $table $this->where"));
            //print_r($total);
            $total = $total["count($this->primaryKey)"];
            //echo "$total : select $fields from $table $this->where";
?>
            <table border="0" align="center" id="tableCreator">
                <thead>
                <td colspan="<?=count($this->getDisplayedFieldName())
?>"><?=$total
?>
        		sonuç içinde gösterilen <?=$after
?> - <?=$this->defaultPerPage + $after ?></td>
            <td><a href="?page=<?=$this->table ?>&process=insert">Veri Ekle</a></td>
</thead>
<tr class="displayNames">
        <? foreach ($this->fields as $fieldName => $fieldsConfig): ?>
<? if ($this->fields [$fieldName] ['display']) : ?>
            <? // 0 sort asc; 1 sort desc  ?>
        <td>
<? if ($this->fields[$fieldName]['sortable']) : ?>
                <a href="?page=<?=$this->table ?>&pages=<?=$page ?>&sortBy=<?=$fieldName ?>&sortType=<?=$sortType ?>">
                                 <?=$this->fields [$fieldName] ['displayName'] ?>
        </a>

    <? else: ?>
                                                    <?=$this->fields [$fieldName] ['displayName'] ?>
<? endif ?>
    </td>
<? endif ?>
<? endforeach ?>
        <td>İşlemler</td>
    </tr>
<? foreach ($results as $k => $valueArr): ?>
    <tr id="<?=$valueArr[$this->primaryKey] ?>">
    <? foreach ($valueArr as $fieldName => $value): ?>
<? if ($this->fields [$fieldName] ['display']): ?>
                <td style="width: 200px;"><?=$value ?></td>
<? endif ?>
<? endforeach ?>
                    <td style="width: 200px;"><a class="delete" href="?page=<?=$this->table ?>&process=delete&id=<?=$valueArr[$this->primaryKey] ?>">sil</a>
        <a href="?page=<?=$this->table ?>&process=update&id=<?=$valueArr[$this->primaryKey] ?>">düzenle</a> <a href="<?=$_SERVER["REQUEST_URI"] ?>&process=view&id=<?=$valueArr [$this->primaryKey] ?>">görüntüle</a></td>
</tr>
<? endforeach ?>
<tr> 
    <td colspan="<?=count($this->getDisplayedFieldName()) + 1 ?>">
<? for ($i = 1; $i <= ceil($total / $this->defaultPerPage); $i++): ?>
        <a href="?page=<?=$this->table ?>&pages=<?=$i ?>"><?=$i ?></a>
<? endfor ?></td>
</tr>
</table>
<?php
}

function save() {
if (!empty($_POST)) {
    $values = '';
    $fields = '';
    foreach ($this->fields as $fieldName => $fieldsConfig) {
        if (($fieldsConfig['input_type'] == 'text' || $fieldsConfig['input_type'] == 'textarea' || $fieldsConfig['input_type'] == 'selective' || $fieldsConfig['input_type'] == 'checkbox' || $fieldsConfig['input_type'] == 'radio') && $this->primaryKey != $fieldName) {
            $fields .= "$fieldName, ";
            $values .="'" . $_POST[$fieldName] . "', ";
            if ($fieldsConfig['required'] && $_POST[$fieldName] == "")
                $this->addError("$fieldsConfig[displayName] is required.");
        }
        else if ($fieldsConfig['input_type'] == 'file') {
            if (!empty($_FILES))
                $new_name = @h_file_upload($_FILES[$fieldName], '', "upload/");
            else
                $new_name = '';
            $fields .= "$fieldName, ";
            $values .= "'$new_name', ";
            if ($fieldsConfig['required'] && empty($_FILES))
                $this->addError("$fieldsConfig[displayName] is required.");
        }

        else if ($fieldsConfig['input_type'] == 'password') {
            $fields .= "$fieldName, ";
            $values .="'" . md5($_POST[$fieldName]) . "', ";
            if ($fieldsConfig['required'] && $_POST[$fieldName] == "")
                $this->addError("$fieldsConfig[displayName] is required.");
        }
    }
    $fields = substr($fields, 0, -2);
    $values = substr($values, 0, -2);
    //echo "insert into $this->table ($fields) values($values)";

    if (empty($this->errors)) {
        if ($this->dbQuery("insert into $this->table ($fields) values($values)"))
            return True;
        else
            $this->addSystemError("Insertion problem");
    }
    else
        return false;
}
return false;
}

function update($id) {
if (!empty($_POST)) {
    $values = '';
    foreach ($this->fields as $fieldName => $fieldsConfig) {
        if (($fieldsConfig['input_type'] == 'text' || $fieldsConfig['input_type'] == 'textarea' || $fieldsConfig['input_type'] == 'selective' || $fieldsConfig['input_type'] == 'checkbox' || $fieldsConfig['input_type'] == 'radio') && $this->primaryKey != $fieldName) {
            $value = mysql_real_escape_string($_POST[$fieldName]);
            $update .= "$fieldName = '$value',  ";
            if ($fieldsConfig['required'] && $_POST[$fieldName] == "")
                $this->addError("$fieldsConfig[displayName] is required.");
        }
        else if ($fieldsConfig['input_type'] == 'file') {
            $new_name = @h_file_upload($_FILES[$fieldName], '', "upload/");
            $update .= "$fieldName = '$new_name', ";
            if ($fieldsConfig['required'] && empty($_FILES))
                $this->addError("$fieldsConfig[displayName] is required.");
        }
        else if ($fieldsConfig['input_type'] == 'password') {
            if ($_POST[$fieldName] != "") {
                $value = "$fieldName, ";
                $update .="$fieldName='" . md5(mysql_real_escape_string($_POST[$fieldName])) . "', ";
            }
            if ($fieldsConfig['required'] && $_POST[$fieldName] == "")
                $this->addError("$fieldsConfig[displayName] is required.");
        }
    }
    $update = substr($update, 0, -3);
    //echo "update $this->table set $update where $this->primaryKey = $id";
    if ($this->dbQuery("update $this->table set $update where $this->primaryKey = $id"))
        return True;
    else
        return False;
}
return false;
}

function generateInsertForm() {
if (!empty($_POST))
    if ($this->save())
        echo "Başarıyla Eklendi";
    else
        $this->displayError($this->errors);
$name = $this->getDisplayedFieldName();
echo $name;
?>

<form action="" method="post" name="generateForm" target="_self" enctype="multipart/form-data">
    <table class="tableCreator">
                <? foreach ($this->fields as $fieldName => $fieldsConfig): ?>
<? if ($this->fields [$fieldName] ['allowInsert'] && $this->primaryKey != $fieldName): ?>
                <tr>
                    <td>
            			<?=$this->fields [$fieldName] ['displayName'] ?>
                    </td>
                    <td><? switch ($this->fields [$fieldName]["input_type"]) {
                            case "text": ?>
                                <input name="<?=$fieldName; ?>" type="text" value="<?=$this->fields [$fieldName] ['default_value'] ?>">
                    <?php break; ?>
                    <?php case "selective": ?>
                    <select name="<?=$fieldName ?>">
                    <? if ($this->fields [$fieldName]): ?>
                    <?php
                        $foreign_field = $this->fields [$fieldName] ['foreign_field'];
                        $foreign_displayField = $this->fields [$fieldName] ['foreign_displayField'];
                        $foreign_table = $this->fields [$fieldName] ['foreign_table'];
                        $allData = $this->getTableResult("select $foreign_field,$foreign_displayField from  $foreign_table");
                        //print_r($allData);
                        foreach ($allData as $k => $value): ?>
                            <option value="<?=$value[$foreign_field] ?>"><?=$value [$foreign_displayField] ?></option>
                    <? endforeach ?>
                    <? endif ?>
                    <? /*
                      Bu kısma default value kısmını yap
                      <option>Volvo</option>
                      <option>Saab</option>
                      <option>Mercedes</option>
                      <option>Audi</option>
                      <?
                      } */
                    ?>
                    </select>
<?
                    break;
                case "textarea" :
?>
                <textarea name="<?=$fieldName;
?>"></textarea>
<?
                          break;
                      case "file" :
?>
                          <input name="<?=$fieldName; ?>" type="file" value="<?=$this->fields [$fieldName] ['default_value'] ?>">
<?php
                break;
            case "checkbox":
?>
                <input name="<?=$fieldName; ?>" type="checkbox" value="1" />
<?
                break;
            case "radio":
?>
                <input name="<?=$fieldName; ?>" type="radio" value="1" />
<?
                break;
            case "password" :
?>
                <input name="<?=$fieldName; ?>" type="password" value="<?=$this->fields [$fieldName] ['default_value'] ?>">
                <?php
                break;
        }

        //end swich
                ?>
            </td>
        </tr>
<? endif ?>
<? endforeach ?>
        <tr>
            <td colspan="2"><input type="submit" name="send" value="Gönder"></td>
        </tr>
    </table>
</form>
<?php
    }

    function generateUpdateForm($value) {
        if (!empty($_POST))
            if ($this->update($_GET['id']))
                echo "Başarıyla düzenlendi";
            else
                $this->displayError($this->errors);
        $name = $this->getDisplayedFieldName();
        list($fields, $table) = $this->getQueryTablesAndFields();
        //$this->setDisplayFilter($joinClause, "and");
        $this->setDisplayFilter("$this->primaryKey = $value", "and");
        //echo "$this->where";
        $sql = "select $fields from $table $this->where";
        //echo $sql;
        $resource = $this->dbQuery($sql);
        $i = mysql_fetch_assoc($resource);
        //print_r($i);
?>

<form action="" method="post" name="generateForm" target="_self" enctype="multipart/form-data">
    <table class="tableCreator">
<? foreach ($this->fields as $fieldName => $fieldsConfig): ?>
<? if ($this->fields [$fieldName] ['allowInsert'] && $this->primaryKey != $fieldName): ?>


                <tr>
                    <td>
                    <?=$this->fields [$fieldName] ['displayName'] ?>
            </td>
                <? if ($this->fields [$fieldName] ['functionName'] === False): ?>
                <td><? switch ($this->fields [$fieldName] ["input_type"]) {
                        case "text": ?>
                            <input name="<?=$fieldName; ?>" type="text" value="<?=$i[$fieldName] ?>">
                    <?php break; ?>
                    <?php case "selective": ?>
                    <select name="<?=$fieldName ?>">
                    <? if ($this->fields [$fieldName]): ?>
                    <?php
                        $foreign_field = $this->fields [$fieldName] ['foreign_field'];
                        $foreign_displayField = $this->fields [$fieldName] ['foreign_displayField'];
                        $foreign_table = $this->fields [$fieldName] ['foreign_table'];
                        $allData = $this->getTableResult("select $foreign_field,$foreign_displayField from  $foreign_table");
                        foreach ($allData as $k => $value): ?>
                    <option value="<?=$value[$foreign_field] ?>" <?php if ($value [$foreign_displayField] == $i[$fieldName])
                                echo 'SELECTED'; ?>><?=$value [$foreign_displayField] ?></option>
                            <? endforeach ?>
                            <? endif ?>
                            <? /*
                              Bu kısma default value kısmını yap
                              <option>Volvo</option>
                              <option>Saab</option>
                              <option>Mercedes</option>
                              <option>Audi</option>
                              <?
                              } */
                            ?>
                            </select>
<?
                            break;
                        case "textarea" :
?>

                            <br />
                            <textarea name="<?=$fieldName; ?>"><?=$i[$fieldName] ?> </textarea>
<?
                break;
            case "file" :
?>
                Şu an: <?=$i[$fieldName] ?>
                <br />
                <input name="<?=$fieldName; ?>" type="file" value="<?=$this->fields [$fieldName] ['default_value'] ?>">
                <?php
                break;
            case "checkbox":
                $checked = "";
                if ($i[$fieldName] == "1")
                    $checked = "CHECKED";
                ?>

                <input name="<?=$fieldName ?>" type="checkbox" value="1" <?=$checked ?>/>
                <?
                break;
            case "radio":
                $checked = "";
                if ($i[$fieldName] == "1")
                    $checked = "CHECKED";
                ?>
                <input name="<?=$fieldName;
                ?>" type="radio" value="1" <?=$checked
                ?> />
<?
                       break;
                   case "password" :
?>
                <input name="<?=$fieldName;
?>" type="password">
<?php break; ?>

            </td>
        </tr>
        <?
                   }
               //end swich
               else: ?>
        <td>
                 <? $this->fields [$fieldName] ['functionName']($id, $sql, $resource)?></td></tr>
        
<? endif; ?>
<? endif; ?>
<? endforeach; ?>
                   <tr>
                       <td colspan="2"><input type="submit" name="send" value="Gönder"></td>
                   </tr>
               </table>
           </form>
<?php
               }

               function display() {
                   if (empty($_GET['process']))
                       $this->displayTable($_GET ['pages']);
                   if ($_GET ['process'] == 'view')
                       $this->displayRow($_GET ['id']);
                   else if ($_GET ['process'] == 'insert')
                       $this->generateInsertForm();
                   else if ($_GET ['process'] == 'delete') {
                       if ($this->deleteRow($_GET[id]))
                           echo '<a href="admin.php?page=' . $_GET[page] . '">Başarıyla, silindi geri dönün';
                   }
                   else if ($_GET ['process'] == 'update') {
                       $this->generateUpdateForm($_GET[id]);
                   }
               }

           }
?>