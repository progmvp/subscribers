<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Админка — платежи</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #0f172a;
            color: #fff;
            padding: 20px;
        }

        .top {
            margin-bottom: 20px;
        }

        .top a {
            color: #fff;
            margin-right: 15px;
            text-decoration: none;
        }

        h1 {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #1e293b;
            border-radius: 12px;
            overflow: hidden;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #334155;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: #111827;
        }

        tr:hover {
            background: #243244;
        }

        .pending {
            color: orange;
        }

        .success {
            color: lightgreen;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
            white-space: nowrap;
        }

        .btn-confirm {
            background: #22c55e;
            color: #000;
        }

        .btn-delete {
            background: #ef4444;
            color: #fff;
        }
    </style>
</head>

<body>

<div class="top">
    <a href="/admin/logout">Выйти</a>
</div>

<h1>Платежи</h1>

<table>
    <tr>
        <th>Имя</th>
        <th>Email</th>
        <th>ID</th>
        <th>Сумма</th>
        <th>Время</th>
        <th>Статус</th>
        <th>Действия</th>
    </tr>

    @foreach($payments as $p)

    <tr>
        <td>{{ $p->name }}</td>

        <td>{{ $p->email }}</td>

        <td>{{ $p->payment_id }}</td>

        <td>{{ $p->amount }}</td>

        <td>{{ $p->created_at }}</td>

        <td class="{{ $p->status }}">
            {{ $p->status }}
        </td>

        <td>

            <div class="actions">

                @if($p->status === 'pending')

                    <a class="btn btn-confirm"
                       href="/admin/payments/confirm?id={{ $p->id }}">
                        Подтвердить
                    </a>

                @endif

                <a class="btn btn-delete"
                   href="/admin/payments/delete?id={{ $p->id }}"
                   onclick="return confirm('Удалить запись без возможности восстановления?');">
                    Удалить
                </a>

            </div>

        </td>
    </tr>

    @endforeach

</table>

</body>
</html>