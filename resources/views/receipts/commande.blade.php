<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu Commande #{{ $commande->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #333;
            margin: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #c8960c;
            font-size: 24px;
            margin: 0;
        }
        .header p {
            color: #666;
            margin: 5px 0;
        }
        .section {
            margin-bottom: 20px;
        }
        .section h3 {
            border-bottom: 2px solid #c8960c;
            padding-bottom: 5px;
            color: #c8960c;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table th {
            background-color: #c8960c;
            color: white;
            padding: 8px;
            text-align: left;
        }
        table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .total {
            text-align: right;
            font-size: 16px;
            font-weight: bold;
            margin-top: 15px;
            color: #c8960c;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            color: #999;
            font-size: 11px;
        }
        .badge {
            background-color: #c8960c;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>🍯 Khayrate Bladi</h1>
        <p>Produits du Terroir Marocain</p>
        <p>Email : contact@khayrate-bladi.ma | Tél : +212 600 000 000</p>
    </div>

    <div class="section">
        <h3>Informations de la Commande</h3>
        <p><strong>Numéro de commande :</strong> #{{ $commande->id }}</p>
        <p><strong>Date :</strong> {{ $commande->created_at->format('d/m/Y H:i') }}</p>
        <p><strong>Statut :</strong> <span class="badge">{{ strtoupper($commande->statut) }}</span></p>
    </div>

    <div class="section">
        <h3>Informations du Client</h3>
        <p><strong>Nom :</strong> {{ $user->nom }} {{ $user->prenom }}</p>
        <p><strong>Email :</strong> {{ $user->email }}</p>
        @if($user->telephone)
        <p><strong>Téléphone :</strong> {{ $user->telephone }}</p>
        @endif
    </div>

    <div class="section">
        <h3>Détail des Articles</h3>
        <table>
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Prix Unitaire</th>
                    <th>Quantité</th>
                    <th>Sous-total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($articles as $article)
                <tr>
                    <td>{{ $article->produit->nom }}</td>
                    <td>{{ number_format($article->prix_unitaire, 2) }} DH</td>
                    <td>{{ $article->quantite }}</td>
                    <td>{{ number_format($article->prix_unitaire * $article->quantite, 2) }} DH</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total">
            Total : {{ number_format($commande->prix_total, 2) }} DH
        </div>
    </div>

    <div class="footer">
        <p>Merci pour votre confiance ! — Khayrate Bladi</p>
        <p>Ce document est généré automatiquement, il est valable comme reçu de commande.</p>
    </div>

</body>
</html>