# vmestecdn_php

АПИ для видеохостинга vmestecdn на языке PHP

## Где найти токен

Токен можно найти на странице профиля в видеохостинге

## Функции
### Загрузка видео

```php

$result = $uploads->upload_file("/path/to/file.avi"); // загрузка файлов с вашего сервера

// загрузка через форму
if(isset($_FILES['file'])){
    $result = $uploads->upload_form($_FILES['file']));
}

// получение рузльтата загрузки
$data = json_decode($result, true); // преобразуем JSON в массив
$data['result']; // результат загрузки success/error
$uuid = $data['uuid']; // идентификатор загруженного файла, если result: success;

//либо можно использоват готовые функции
$uploads->result_upload(); // результат загрузки success/error
$uploads->uuid_upload(); // идентификатор загруженного файла, если result: success;

```
### Получение информации
```php
$uploads->info($uuid); // вернется ответ в формате JSON с информацией о файле
//После вызова этой функции станут доступны следующие фукнции
$uploads->getActive(); // активен ли файл
$uploads->getIframe(); // ссылка на плеер
$uploads->getDuration(); // продолжительность
$uploads->getPreview(); // превью
```

### Удаление
```php
$uploads->delete($uuid); // вернется ответ в формате JSON с рузльтатом удаления или текстом ошибки
//После вызова функции удаления становятся доступны следующие функции
$uploads->getResultDelete(); // true или false
// если status = failure, то 
$uploads->getErrorDelete(); // вернет текст ошибки, если status = success, вернется null
```
