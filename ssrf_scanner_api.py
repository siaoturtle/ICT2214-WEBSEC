from flask import Flask, request, jsonify
import subprocess
import os
import threading

app = Flask(__name__)

SCAN_OUTPUT_DIR = "scanner_results"

# Ensure output directory exists
if not os.path.exists(SCAN_OUTPUT_DIR):
    os.makedirs(SCAN_OUTPUT_DIR)

def run_scanner(url):
    """ Runs the SSRF scanner in a separate thread """
    output_file = os.path.join(SCAN_OUTPUT_DIR, f"{url.replace('/', '_')}.json")
    cmd = f"python3 ssrf_scanner.py -u {url} -o {output_file}"

    try:
        subprocess.run(cmd, shell=True, check=True)
        return output_file
    except subprocess.CalledProcessError as e:
        print(f"Scanner Error: {e}")
        return None

@app.route('/scan', methods=['POST'])
def scan_url():
    data = request.get_json()
    url = data.get('url')

    if not url:
        return jsonify({"error": "No URL provided"}), 400

    # Run the scanner asynchronously
    scan_thread = threading.Thread(target=run_scanner, args=(url,))
    scan_thread.start()

    return jsonify({"message": f"Scanning started for {url}"}), 200

@app.route('/results', methods=['GET'])
def get_scan_results():
    files = os.listdir(SCAN_OUTPUT_DIR)
    scan_data = {}

    for file in files:
        with open(os.path.join(SCAN_OUTPUT_DIR, file), 'r') as f:
            scan_data[file] = f.read()

    return jsonify(scan_data), 200

if __name__ == '__main__':
    app.run(host='127.0.0.1', port=5000, debug=True)
