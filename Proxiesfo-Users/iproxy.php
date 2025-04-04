<?php
$CoderMarkPrinted = false;

$cl = [
    'gr' => "\033[32m",
    'br' => "\033[34m",
    'red' => "\033[31m",
    'yl' => "\033[33m",
    'gb' => "\033[4m",
    'or' => "\033[35m",
    'cy' => "\033[36m",
    'oc' => "\033[95m",
    'am' => "\033[91m",
    'rt' => "\033[0m"
];

// Check if cURL extension is installed
if (!extension_loaded('curl')) {
    $installInstructions = "Error: PHP cURL extension is not installed.\n\n";
    $installInstructions .= "Installation instructions:\n\n";
    $installInstructions .= "For Windows:\n";
    $installInstructions .= "1. Open php.ini file\n";
    $installInstructions .= "2. Uncomment extension=curl\n";
    $installInstructions .= "3. Restart your web server\n\n";
    $installInstructions .= "For Linux (Ubuntu/Debian):\n";
    $installInstructions .= "sudo apt-get install php-curl\n";
    $installInstructions .= "sudo service apache2 restart\n\n";
    $installInstructions .= "For macOS:\n";
    $installInstructions .= "1. Using Homebrew: brew install php@8.x\n";
    $installInstructions .= "2. Or modify php.ini to enable curl extension\n";
    die($cl['red'] . $installInstructions . $cl['rt']);
}

function CoderMark($cl, &$CoderMarkPrinted) {
    if (!$CoderMarkPrinted) {
        echo "
╭━━━╮╱╱╱╱╱╱╱╱╱╱╱╱╱╭━━━┳╮
┃╭━━╯╱╱╱╱╱╱╱╱╱╱╱╱╱┃╭━━┫┃{$cl['gr']}
┃╰━━┳╮╭┳━┳━━┳━━┳━╮┃╰━━┫┃╭╮╱╭┳━╮╭━╮
┃╭━━┫┃┃┃╭┫╭╮┃╭╮┃╭╮┫╭━━┫┃┃┃╱┃┃╭╮┫╭╮╮{$cl['br']}
┃┃╱╱┃╰╯┃┃┃╰╯┃╰╯┃┃┃┃┃╱╱┃╰┫╰━╯┃┃┃┃┃┃┃
╰╯╱╱╰━━┻╯╰━╮┣━━┻╯╰┻╯╱╱╰━┻━╮╭┻╯╰┻╯╰╯{$cl['rt']}
╱╱╱╱╱╱╱╱╱╱╱┃┃╱╱╱╱╱╱╱╱╱╱╱╭━╯┃
╱╱╱╱╱╱╱╱╱╱╱╰╯╱╱╱╱╱╱╱╱╱╱╱╰━━╯
\n{$cl['gr']} Proxy IP Checker {$cl['am']} v.0.1 {$cl['rt']}
{$cl['gr']}--------------------------------------
\n{$cl['yl']}[+]{$cl['rt']} GH : {$cl['br']}https://github.com/cmalf/
\n{$cl['gr']}--------------------------------------
        ";
        $CoderMarkPrinted = true;
    }
}

function checkProxy($proxyLine) {
    try {
        $parts = explode('://', $proxyLine);
        if (count($parts) !== 2) {
            throw new Exception('Invalid proxy format');
        }
        
        [$protocol, $rest] = $parts;
        
        if (!function_exists('curl_init')) {
            throw new Exception('cURL extension is not installed');
        }
        
        $ch = curl_init();
        if ($ch === false) {
            throw new Exception('Failed to initialize cURL');
        }
        
        curl_setopt($ch, CURLOPT_URL, 'https://ipwhois.app/json/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_PROXY, $rest);
        curl_setopt($ch, CURLOPT_PROXYTYPE, $protocol === 'http' ? CURLPROXY_HTTP : CURLPROXY_SOCKS5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36');

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new Exception($error);
        }

        if ($httpCode !== 200) {
            throw new Exception("HTTP Error: $httpCode");
        }

        $data = json_decode($response, true);
        if (!$data) {
            throw new Exception('Invalid response from server');
        }

        return [
            'proxy' => $proxyLine,
            'success' => true,
            'ip' => $data['ip'],
            'country' => $data['country'],
            'region' => $data['region'],
            'city' => $data['city']
        ];
    } catch (Exception $error) {
        return [
            'proxy' => $proxyLine,
            'success' => false,
            'error' => $error->getMessage()
        ];
    }
}

function checkProxyList() {
    global $cl;
    try {
        echo "{$cl['yl']}Enter List Proxies File Name To check: {$cl['rt']}";
        $filename = trim(readline());
        
        if (!file_exists($filename)) {
            throw new Exception("File not found: $filename");
        }

        $proxyList = array_filter(
            array_map(
                'trim',
                file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
            )
        );

        echo "\nLoading " . count($proxyList) . " proxies...\n";

        $validProxies = [];
        foreach ($proxyList as $proxy) {
            $result = checkProxy($proxy);
            
            if ($result['success']) {
                // New logic: accept proxy if country is NOT United States.
                if ($result['country'] !== 'United States') {
                    echo "{$cl['br']}{$proxy}{$cl['rt']} -> IP: {$cl['gr']}{$result['ip']}{$cl['rt']} ({$result['country']}) ✓\n";
                    $validProxies[] = $proxy;
                } else {
                    echo "{$cl['red']}{$proxy}{$cl['rt']} -> IP: {$cl['gr']}{$result['ip']}{$cl['rt']} ({$result['country']}) ✗\n";
                }
            } else {
                echo "{$cl['red']}{$proxy}{$cl['rt']} -> {$cl['oc']}{$result['error']}{$cl['rt']} ✗\n";
            }
        }

        file_put_contents($filename, implode("\n", $validProxies));

        echo "\nSummary:\n";
        echo "Total proxies processed: " . count($proxyList) . "\n";
        echo "Valid non-US proxies saved: " . count($validProxies) . "\n";
        echo "Removed proxies: " . (count($proxyList) - count($validProxies)) . "\n";
        echo "\nProxy list has been updated with only working non-US proxies.\n";

    } catch (Exception $error) {
        echo "{$cl['red']}Error: {$cl['rt']}" . $error->getMessage() . "\n";
    }
}

// Run the checker
CoderMark($cl, $CoderMarkPrinted);
checkProxyList();
?>
