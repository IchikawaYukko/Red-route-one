<?php
class iZettleProAggregate {
    private $db;
    public function __construct() {
        $this->db = new PDO('sqlite:./izettle.sqlite', null, null);

        $this->db->query('PRAGMA journal_mode=OFF');
        $this->db->query('PRAGMA synchronous=OFF');
        $this->db->query('PRAGMA count_changes=OFF');
        $this->db->query('PRAGMA temp_score=OFF');
        
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);

        $this->db->query('DROP TABLE fulltransaction;');
        $this->db->query('CREATE TABLE IF NOT EXISTS fulltransaction(
        orderid int, _type text, product text, category text,
        gross real, tax real, net real, payment text);');
    }

    public function import_fulltransaction_csv(string $csv) {
        // Delete ByteOrderMark(BOM)
		//return preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $csv);
        $parsed = explode("\n", $csv);
        $parsed = array_map('str_getcsv', $parsed);

        foreach($parsed as $index => $line) {
            if(isset($line[8])) {
                $stmt = $this->db->prepare('INSERT INTO fulltransaction(orderid,
                _type, product, category, gross, tax, net, payment)
                VALUES(?, ?, ?, ?, ?, ?, ?, ?);');

                $stmt->bindValue(1, $line[1]);
                $stmt->bindValue(2, $line[8]);
                $stmt->bindValue(3, $line[9]);
                $stmt->bindValue(4, $line[13]);
                $stmt->bindValue(5, $line[15]);
                $stmt->bindValue(6, $line[16]);
                $stmt->bindValue(7, $line[17]);
                $stmt->bindValue(8, $line[18]);

                $stmt->execute();
            }
        }
    }

    public function get_product_mix_html() :string {
        $header = [
            [
            'Product',
            'Category',
            'Qty',
            'Gross',
            'Tax',
            'Net'
            ]
        ];

        $product_mix = 
            array_merge(
                $header, 
                    $this->format_product_mix_amount(
                        $this->get_product_mix_array()));

        return $this->array2html($product_mix);
    }

    public function get_product_mix_array() :array {
        $stmt = $this->db->query(
            "SELECT * FROM (SELECT product, category, count(product) AS sold, sum(gross),
            sum(tax), sum(net)
            FROM fulltransaction
            WHERE _type = 'Product'
            GROUP BY product, category
            ORDER BY sold)

            UNION ALL

            SELECT * FROM (SELECT '--TOTAL--' AS product, '' AS category, 
            count(product), round(sum(gross), 2),
            round(sum(tax), 2), round(sum(net), 2)
            FROM fulltransaction
            WHERE _type = 'Product')
        ;");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_sales_summary_html() :string {
        $html = 'Sales Summary<br />';
        $html .= $this->array2html($this->format_sales_summary_amount($this->get_sales_summary_array()));
        $html .= $this->array2html($this->get_sales_transactions_array());
        $html .= 'Sales by Category<br />';
        $html .= $this->array2html($this->format_sales_summary_amount($array = $this->get_sales_by_category_array()));

        return $html;
    }

    public function get_sales_by_category_array() :array {
            $stmt = $this->db->query("SELECT category, round(sum(gross), 2)
            FROM fulltransaction WHERE _type = 'Product' GROUP BY category ORDER BY category
            ;");

            return $stmt->fetchAll(PDO::FETCH_NUM);
    }

    public function get_sales_transactions_array() :array {
        $stmt = $this->db->query(
        "SELECT 'Number of Transactions' AS div, count(_type)
        FROM fulltransaction WHERE _type = 'Product'
        UNION ALL
        SELECT 'Number of Guest' AS div, count( DISTINCT orderid)
        FROM fulltransaction WHERE _type = 'Product'
        ;");

        return $stmt->fetchAll(PDO::FETCH_NUM);
    }
    public function get_sales_summary_array() :array {
        $stmt = $this->db->query(
            "SELECT 'Gross Sales' AS div, round(sum(gross), 2)
            FROM fulltransaction WHERE _type = 'Product'
            UNION ALL
            SELECT 'Net Sales' AS div, round(sum(net), 2) FROM fulltransaction WHERE _type = 'Product'
            UNION ALL
            SELECT 'Sales Taxes' AS div, round(sum(tax), 2) FROM fulltransaction WHERE _type = 'Product'
            UNION ALL
            SELECT payment, round(sum(gross), 2) FROM fulltransaction WHERE payment <> 'PaymentType' AND _type = 'Product' GROUP BY payment
            UNION ALL
            SELECT 'Average per Guest(Net)' AS div, round(sum(net)/count( DISTINCT orderid), 2)
            FROM fulltransaction WHERE _type = 'Product'
        ;");

        return $stmt->fetchAll(PDO::FETCH_NUM);
    }

    private function format_amount($value) :string{
        $currency = '&pound;';
        return $currency.number_format((float) $value,2);
    }

    private function format_sales_summary_amount(array $ary) :array{
        foreach ($ary as &$value) {
            $value[1] = $this->format_amount($value[1]);
        }
        return $ary;
    }
    private function format_product_mix_amount(array $ary) :array {
        foreach ($ary as &$value) {
            $value['sum(gross)'] = $this->format_amount($value['sum(gross)']);
            $value['sum(tax)'] = $this->format_amount($value['sum(tax)']);
            $value['sum(net)'] = $this->format_amount($value['sum(net)']);
        }
        return $ary;
    }

	public function csv2html($csv) {
		$parsed = explode("\n", $csv);
        $parsed = array_map('str_getcsv', $parsed);

        return $this->array2html($parsed);
    }

    public function array2html(array $ar) :string {
        $html = "<table border>\n";

        foreach($ar as $line) {
            $html .= "<tr>";
            foreach($line as $col) {
                $html .= "<td>$col</td>";
            }
            $html .= "</tr>\n";
        }

        $html .= "</table>\n";

        return $html;
    }
}