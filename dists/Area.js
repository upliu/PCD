/**
 * Created by upliu on 3/26/16.
 */
var jQuery, AreaData, Area;

if (!AreaData) {
    alert('can not find area data');
}
/**
 * 
 * @param p 省选择器
 * @param c 市选择器
 * @param d 区选择器
 * @constructor
 */
Area = function(p, c ,d) {

    var $province = $(p),
        $city = $(c),
        $district = $(d);

    init();
    
    function init() {
        setArea($province, 0);
        setProvince(AreaData['id0'][0]);
        $province.change(function () {
            setProvince($(this).val());
        });
        $city.change(function () {
            setCity($(this).val());
        });
    }
    function setProvince(id) {
        setArea($city, id);
        setCity(AreaData['id'+id][0]);
    }
    function setCity(id) {
        setArea($district, id);
    }
    function setArea($dom, pid) {
        $dom.children().remove();
        var names = AreaData['name'+pid];
        var ids = AreaData['id'+pid];
        for (var i in ids) {
            $dom.append(
                $('<option>').text(names[i]).val(ids[i])
            );
        }
    }
};