<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body { 
            font-family: 'Georgia', serif; 
            color: #1a1a1a; 
            margin: 0; 
        }

        /* Sidebar Color Accent */
        .sidebar {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 10px;
            background-color: #d4af37; /* Gold Accent */
        }

        .main-content { margin-left: 40px; padding: 60px 50px; }

        /* Header Layout */
        .header-box { border-bottom: 3px solid #1a1a1a; padding-bottom: 20px; margin-bottom: 40px; }
        .company-logo { height: 60px; filter: grayscale(100%); }
        
        .title-large { 
            font-size: 32px; 
            font-weight: bold; 
            text-transform: uppercase; 
            letter-spacing: 4px;
            margin: 0;
        }

        /* Information Section */
        .info-grid { margin-bottom: 50px; }
        .info-col { width: 50%; vertical-align: top; }
        .section-label { 
            font-family: 'Helvetica', sans-serif;
            font-size: 10px; 
            color: #d4af37; 
            font-weight: bold; 
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        /* Table Design: High Contrast */
        table { width: 100%; border-collapse: collapse; }
        .table-prestige thead th {
            background-color: #1a1a1a;
            color: #ffffff;
            padding: 15px;
            font-family: 'Helvetica', sans-serif;
            font-size: 11px;
            text-align: left;
        }
        .table-prestige tbody td {
            padding: 20px 15px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 13px;
        }

        /* Footer & Total */
        .footer-total { background-color: #f9f9f9; padding: 25px; margin-top: 20px; }
        .amount-big { font-size: 28px; font-weight: bold; color: #1a1a1a; }
        
        .footer-text { 
            position: fixed; 
            bottom: 30px; 
            left: 90px;
            right: 50px;
            font-size: 10px; 
            color: #999; 
            font-family: 'Helvetica', sans-serif;
        }
    </style>
</head>
<body>

    <div class="sidebar"></div>

    <div class="main-content">
        <header class="header-box">
            <table>
                <tr>
                    <td>
                        <img src="{{ public_path('images/logo.png') }}" class="company-logo">
                    </td>
                    <td style="text-align: right;">
                        <h1 class="title-large">PROPOSAL</h1>
                        <p style="margin: 5px 0 0 0; color: #d4af37; font-weight: bold;">Ref: {{ $header->kode }}</p>
                    </td>
                </tr>
            </table>
        </header>

        <table class="info-grid">
            <tr>
                <td class="info-col">
                    <div class="section-label">Prepared For</div>
                    <div style="font-size: 18px; font-weight: bold;">{{ $header->customer_name }}</div>
                    <div style="margin-top: 5px; color: #444; font-size: 13px; line-height: 1.4;">
                        {{ $header->customer_address }}<br>
                        T: {{ $header->customer_phone }}
                    </div>
                </td>
                <td class="info-col" style="padding-left: 40px;">
                    <div class="section-label">Issue Date</div>
                    <div style="font-size: 14px; margin-bottom: 20px;">{{ date('j F Y', strtotime($header->tanggal)) }}</div>
                    
                    <div class="section-label">Project Manager</div>
                    <div style="font-size: 14px;">{{ $header->user_name }}</div>
                    <div style="font-size: 13px; color: #666;">{{ $header->user_email }}</div>
                </td>
            </tr>
        </table>

        <table class="table-prestige">
            <thead>
                <tr>
                    <th width="50%">Description of Services</th>
                    <th width="10%">Qty</th>
                    <th style="text-align: right;">Rate</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @php $grandTotal = 0; @endphp
                @foreach($details as $item)
                <tr>
                    <td>
                        <strong style="font-size: 14px;">{{ $item->category_name }}</strong><br>
                        <span style="color: #666; font-style: italic;">Route: {{ $item->kota_asal }} to {{ $item->kota_tujuan }}</span>
                    </td>
                    <td>{{ $item->jumlah }}</td>
                    <td style="text-align: right;">{{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                    <td style="text-align: right; font-weight: bold;">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @php $grandTotal += $item->subtotal; @endphp
                @endforeach
            </tbody>
        </table>

        <div class="footer-total">
            <table width="100%">
                <tr>
                    <td>
                        <div class="section-label">Total Investment</div>
                        <div class="amount-big">IDR {{ number_format($grandTotal, 0, ',', '.') }}</div>
                    </td>
                    <td style="text-align: right; vertical-align: bottom;">
                        <p style="font-size: 11px; color: #777; margin: 0;">* All prices are subject to prevailing terms & conditions</p>
                    </td>
                </tr>
            </table>
        </div>

        <table style="margin-top: 80px;">
            <tr>
                <td width="30%" style="text-align: center;">
                    <div style="border-bottom: 1px solid #1a1a1a; margin-bottom: 10px;"></div>
                    <div class="section-label">Client Acceptance</div>
                </td>
                <td width="40%"></td>
                <td width="30%" style="text-align: center;">
                    <div style="margin-bottom: 10px;"><strong>{{ $header->user_name }}</strong></div>
                    <div style="border-bottom: 1px solid #d4af37; margin-bottom: 10px;"></div>
                    <div class="section-label">Authorized Representative</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer-text">
        This document is an official proposal and is valid for 14 days from the date of issuance. 
        All business is transacted in accordance with our standard trading conditions.
    </div>

</body>
</html>