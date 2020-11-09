
        function getColor(value, min, max, mirror=0, brithness=0.8) {
            let ratio = value;
            if(max == 0){
              max = 1;
            }
            if (value < min) {
                value = min;
            } else if (value > max) {
                value = max;
            }
            if (min > 1) {
                ratio = value * 1 / min;
            } else {
                ratio = value;
            }
            if(mirror > 0){
              var hue = (max - ratio) * 120 / max;
            }else{
              var hue = ratio * 120 / max;
            }
            var rgb = hslToRgb(hue, 1, brithness);
            return "rgb(" + rgb[0] + "," + rgb[1] + "," + rgb[2] + ")";
        }

        function hslToRgb (h, s, l) {
            if (s === 0) return [l, l, l]
               h /= 360
            var q = l < 0.5 ? l * (1 + s) : l + s - l * s
            var p = 2 * l - q

            return [
                Math.round(hueToRgb(p, q, h + 1/3) * 255),
                Math.round(hueToRgb(p, q, h) * 255),
                Math.round(hueToRgb(p, q, h - 1/3) * 255)
            ]
        }

        function hueToRgb (p, q, t) {
            if (t < 0) t += 1
            if (t > 1) t -= 1
            if (t < 1/6) return p + (q - p) * 6 * t
            if (t < 1/2) return q
            if (t < 2/3) return p + (q - p) * (2/3 - t) * 6

            return p
        }

