<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Statistiques Vendeur</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; color: #333; margin: 40px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #c8960c; font-size: 24px; margin: 0; }
        .section { margin-bottom: 20px; }
        .section h3 { border-bottom: 2px solid #c8960c; padding-bottom: 5px; color: #c8960c; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table th { background-color: #c8960c; color: white; padding: 8px; text-align: left; }
        table td { padding: 8px; border-bottom: 1px solid #ddd; }
        table tr:nth-child(even) { background-color: #f9f9f9; }
        .stat-box { display: inline-block; width: 30%; margin: 10px; padding: 15px; background: #fff8e1; border: 1px solid #c8960c; border-radius: 8px; text-align: center; }
        .stat-val { font-size: 22px; font-weight: bold; color: #c8960c; }
        .stat-lbl { font-size: 12px; color: #666; }
        .footer { text-align: center; margin-top: 40px; color: #999; font-size: 11px; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Khayrate Bladi</h1>
        <p>Rapport de statistiques de vente</p>
        <p>Vendeur : {{ $user->prenom }} {{ $user->nom }} | Date : {{ $date }}</p>
    </div>

    <div class="section">
        <h3>Résumé</h3>
        <div class="stat-box">
            <div class="stat-val">{{ $total_ventes }} DH</div>
            <div class="stat-lbl">Total des ventes</div>
        </div>
        <div class="stat-box">
            <div class="stat-val">{{ $nombre_commandes }}</div>
            <div class="stat-lbl">Commandes reçues</div>
        </div>
    </div>

    <div class="section">
        <h3>Top 5 Produits les plus vendus</h3>
        <table>
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Quantité vendue</th>
                    <th>Revenu total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($top_produits as $item)
                <tr>
                    <td>{{ $item->produit->nom ?? 'N/A' }}</td>
                    <td>{{ $item->total_vendu }}</td>
                    <td>{{ number_format($item->total_revenu, 2) }} DH</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Rapport généré automatiquement — Khayrate Bladi</p>
    </div>

</body>
</html>