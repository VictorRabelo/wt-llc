<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>Estoque</title>
        <style>
            .page-break {
                page-break-after: always;
            }

            img {
                width: 50px;
                height: 50px;
            }

            #customers {
                font-family: Arial, Helvetica, sans-serif;
                border-collapse: collapse;
                width: 100%;
            }

            #customers td, #customers th {
                border: 1px solid #ddd;
                padding: 8px;
            }

            #customers tr:nth-child(even){background-color: #f2f2f2;}

            #customers tr:hover {background-color: #ddd;}

            #customers th {
                padding-top: 10px;
                padding-bottom: 10px;
                background-color: #343a40;
                color: white;
            }

            th,td {
                text-align: center;
                align-items: center;
                vertical-align: middle;
            }

        </style>
    </head>
    <body>
        <table id="customers">
            <tr>
                <th>#ID</th>
                <th>Foto</th>
                <th>Und.</th>
                <th>Preço</th>
                <th>Valor Total</th>
            </tr>
            @foreach ($datas as $data)
                <tr>
                    <td>{{ $data->id_produto }}</td>
                    <td>
                        <img src="{{ storage_path('app/public/'.$data->path) }}" alt="Produto">
                    </td>
                    <td>{{ $data->und }}</td>
                    <td>{{ 'R$ '.number_format($data->preco, 2, ',', '.') }}</td>
                    <td>{{ 'R$ '.number_format($data->valor_total, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            
        </table>
    </body>
</html>