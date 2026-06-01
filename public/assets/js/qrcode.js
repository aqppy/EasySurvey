/**
 * QRCode 生成器封装
 * 基于 qrcode-generator (https://github.com/kazuhikoarase/qrcode-generator)
 * 通过 CDN 加载，无需安装
 */

(function() {
    // CDN 上的 qrcode-generator 库
    var CDN_URL = 'https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js';

    var loaded = false;
    var queue = [];

    function loadScript() {
        return new Promise(function(resolve, reject) {
            if (loaded) {
                resolve();
                return;
            }
            var script = document.createElement('script');
            script.src = CDN_URL;
            script.onload = function() {
                loaded = true;
                resolve();
            };
            script.onerror = function() {
                reject(new Error('无法加载二维码生成库，请检查网络连接'));
            };
            document.head.appendChild(script);
        });
    }

    // QRCode.toCanvas(canvas, text, options, callback)
    window.QRCode = {
        toCanvas: function(canvas, text, options, callback) {
            loadScript().then(function() {
                try {
                    var opts = options || {};
                    var size = opts.width || 290;
                    var margin = opts.margin || 2;
                    var qrSize = 8;

                    // 自动选择合适的二维码版本
                    var qr = null;
                    for (var typeNum = 1; typeNum <= 40; typeNum++) {
                        try {
                            var candidate = qrcode(typeNum, 'M');
                            candidate.addData(text);
                            candidate.make();
                            qr = candidate;
                            break;
                        } catch(e) {
                            // 容量不足，尝试更大的版本
                        }
                    }

                    if (!qr) {
                        callback(new Error('文本过长，无法生成二维码'));
                        return;
                    }

                    var moduleCount = qr.getModuleCount();
                    var cellSize = Math.floor((size - margin * 2) / moduleCount);
                    var actualSize = cellSize * moduleCount + margin * 2;

                    canvas.width = actualSize;
                    canvas.height = actualSize;
                    var ctx = canvas.getContext('2d');

                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, actualSize, actualSize);

                    ctx.fillStyle = '#000000';
                    for (var row = 0; row < moduleCount; row++) {
                        for (var col = 0; col < moduleCount; col++) {
                            if (qr.isDark(row, col)) {
                                ctx.fillRect(
                                    col * cellSize + margin,
                                    row * cellSize + margin,
                                    cellSize,
                                    cellSize
                                );
                            }
                        }
                    }

                    callback(null);
                } catch(e) {
                    callback(e);
                }
            }).catch(callback);
        }
    };
})();
