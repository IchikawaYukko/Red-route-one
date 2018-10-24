<?php
class iZettleProAggregate {
    private $db;
    public function __construct() {
        $this->db = new PDO('sqlite:./test.sqlite', null, null);

        $this->db->query('PRAGMA journal_mode=OFF');
        $this->db->query('PRAGMA synchronous=OFF');
        $this->db->query('PRAGMA count_changes=OFF');
        $this->db->query('PRAGMA temp_score=OFF');
        
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);

        $this->db->query('DROP TABLE fulltransaction;');
        $this->db->query('CREATE TABLE IF NOT EXISTS fulltransaction(
        _type text, product text, category text,
        gross real, tax real, net real);');
    }

    public function import_fulltransaction_csv(string $csv) {
        // Delete ByteOrderMark(BOM)
		//return preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $csv);
        $parsed = explode("\n", $csv);
        $parsed = array_map('str_getcsv', $parsed);

        foreach($parsed as $index => $line) {
            if(isset($line[8])) {
                $stmt = $this->db->prepare('INSERT INTO fulltransaction(
                _type, product, category, gross, tax, net) VALUES(?, ?, ?, ?, ?, ?);');

                $stmt->bindValue(1, $line[8]);
                $stmt->bindValue(2, $line[9]);
                $stmt->bindValue(3, $line[13]);
                $stmt->bindValue(4, $line[15]);
                $stmt->bindValue(5, $line[16]);
                $stmt->bindValue(6, $line[17]);

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
                    $this->format_amount(
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
            FROM fulltransaction)
        ;");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function format_amount(array $ary) {
        foreach ($ary as &$value) {
            $value['sum(gross)'] = '£'.number_format((float) $value['sum(gross)'],2);
            $value['sum(tax)'] = '£'.number_format((float) $value['sum(tax)'],2);
            $value['sum(net)'] = '£'.number_format((float) $value['sum(net)'],2);
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