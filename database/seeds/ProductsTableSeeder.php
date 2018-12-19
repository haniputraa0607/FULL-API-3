<?php

use Illuminate\Database\Seeder;

class ProductsTableSeeder extends Seeder
{
    public function run()
    {
        

        \DB::table('products')->delete();
        
        \DB::table('products')->insert(array (
            0 => 
            array (
                'id_product' => 1,
                'id_product_category' => NULL,
                'product_code' => 'e7a4cdd4-8885-4255-be7a-9b680916288e',
                'product_name' => 'MEN\'S BALANCING SKIN TONER',
                'product_description' => '<p>Mengandung Milk Protein yang digunakan untuk membersihkan dan melembabkan pada bagian wajah dan leher.</p><p style="margin-bottom: 15px; padding: 0px; text-align: justify; font-family: "Open Sans", Arial, sans-serif; font-size: 14px;">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin placerat vehicula metus id faucibus. Nunc vitae arcu finibus, pretium dolor in, semper metus. Cras tristique sollicitudin urna sed pretium. Etiam rutrum tortor facilisis auctor scelerisque. Nam egestas fermentum odio eget porta. Pellentesque eu est ut urna rhoncus malesuada. Mauris libero felis, efficitur eget sem sed, ultrices pulvinar elit. Aenean sed dolor nec lacus rutrum iaculis at vitae tellus. Pellentesque fermentum, urna quis blandit blandit, eros nibh vulputate lacus, eu tristique enim elit nec sem.</p><p style="margin-bottom: 15px; padding: 0px; text-align: justify; font-family: "Open Sans", Arial, sans-serif; font-size: 14px;">In ultricies magna eu mattis vulputate. Phasellus tincidunt, ipsum nec porta porta, ante velit tincidunt ligula, sit amet posuere odio lorem in nisi. Morbi velit massa, blandit at imperdiet quis, maximus vel ex. Sed ac mi mi. Quisque fringilla maximus mollis. Praesent placerat auctor nisl, eget ornare nunc feugiat vitae. Nunc ut libero ac arcu condimentum lacinia. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec egestas ultrices rhoncus. Donec laoreet dolor non eros fringilla, eleifend pulvinar arcu malesuada. Pellentesque dui diam, finibus et feugiat et, volutpat volutpat velit. Nam nec diam facilisis, aliquam mi feugiat, vehicula mauris.</p><p style="margin-bottom: 15px; padding: 0px; text-align: justify; font-family: "Open Sans", Arial, sans-serif; font-size: 14px;">Proin eget neque eu nunc faucibus venenatis rutrum quis tortor. Phasellus suscipit ipsum a placerat egestas. Duis venenatis tincidunt neque quis efficitur. Aliquam erat volutpat. Vestibulum a ipsum urna. Aenean sit amet leo elit. Aliquam ut viverra turpis, et laoreet ligula. Vivamus blandit posuere turpis sed egestas. Sed accumsan malesuada felis, et aliquet ligula efficitur eu. Duis bibendum pharetra ipsum, sed vulputate nisi imperdiet sit amet. Proin blandit sed metus eget aliquet. Nunc sodales et sapien sit amet commodo.</p><p style="margin-bottom: 15px; padding: 0px; text-align: justify; font-family: "Open Sans", Arial, sans-serif; font-size: 14px;">Duis hendrerit semper sagittis. Curabitur vel mauris non urna consequat finibus. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Suspendisse elementum rhoncus ante, nec porttitor magna sagittis a. Fusce id ullamcorper diam. Sed sit amet venenatis risus. Interdum et malesuada fames ac ante ipsum primis in faucibus. Donec eget est tempor, elementum nisl sit amet, dapibus elit.</p><p style="margin-bottom: 15px; padding: 0px; text-align: justify; font-family: "Open Sans", Arial, sans-serif; font-size: 14px;">Interdum et malesuada fames ac ante ipsum primis in faucibus. Donec nec eros a ex maximus tempus at vitae urna. Aenean interdum sed lorem quis varius. Proin finibus aliquet maximus. Sed iaculis diam eget felis sagittis, ut ornare diam pharetra. Phasellus id ante eu ligula tincidunt commodo et quis orci. Curabitur scelerisque varius vestibulum. Nam in erat molestie, ornare tortor nec, suscipit urna. Praesent nibh augue, tempor a pharetra eu, rhoncus at massa.</p><p style="margin-bottom: 15px; padding: 0px; text-align: justify; font-family: "Open Sans", Arial, sans-serif; font-size: 14px;">Vestibulum eleifend augue varius massa gravida mollis. Quisque nec vestibulum erat. Fusce non ligula urna. Aliquam maximus consequat augue, non interdum dolor aliquam sed. Maecenas eros dui, maximus vitae lacinia pharetra, egestas in arcu. Nunc augue leo, pulvinar vitae tortor ut, imperdiet pulvinar felis. Vivamus dapibus lorem lacus, ac venenatis massa tempus id.</p><p style="margin-bottom: 15px; padding: 0px; text-align: justify; font-family: "Open Sans", Arial, sans-serif; font-size: 14px;">Donec urna quam, euismod et congue sed, eleifend ut odio. Quisque semper orci at purus eleifend, sit amet hendrerit quam dignissim. Maecenas a imperdiet lacus. Fusce gravida, ligula pulvinar rutrum suscipit, augue elit ornare ex, rhoncus tempus dui ipsum sed ex. Sed quis porttitor sem. Praesent lorem leo, mattis nec neque vitae, lobortis rhoncus neque. In volutpat nunc nulla, eget vehicula nibh ultrices ut. In a metus consequat, pulvinar nisi non, malesuada est. Suspendisse a dolor euismod, viverra diam ut, sagittis diam. Duis ullamcorper congue elementum. Duis at orci tempus, semper odio sed, facilisis odio.</p><p style="margin-bottom: 15px; padding: 0px; text-align: justify; font-family: "Open Sans", Arial, sans-serif; font-size: 14px;">Nunc tempor, diam eu sollicitudin maximus, nibh nisi mattis leo, sed viverra leo nulla a turpis. Sed lobortis, libero feugiat tincidunt hendrerit, risus turpis tincidunt eros, vitae accumsan neque sapien a mi. Etiam tincidunt mauris ac est vestibulum, ut ultrices dui rutrum. Etiam sit amet efficitur nisl. Phasellus non fermentum quam. Praesent vel leo eget lorem viverra elementum. Nulla iaculis nibh id risus blandit, ut tristique urna fermentum. Praesent lacus sapien, semper at mauris in, sollicitudin congue nibh. Duis vestibulum velit vitae purus condimentum porta eu eu velit.</p><p style="margin-bottom: 15px; padding: 0px; text-align: justify; font-family: "Open Sans", Arial, sans-serif; font-size: 14px;">Praesent in aliquet sapien. Fusce auctor eget orci eget sagittis. Donec vel nunc neque. Pellentesque bibendum neque euismod aliquam tincidunt. Aenean egestas, orci quis vestibulum condimentum, ligula tortor convallis lectus, luctus tincidunt nisi lectus sed neque. Maecenas massa tortor, malesuada a iaculis et, malesuada non urna. In suscipit ante et odio ornare tincidunt. Nam faucibus commodo velit, a placerat nibh interdum nec.</p><p style="margin-bottom: 15px; padding: 0px; text-align: justify; font-family: "Open Sans", Arial, sans-serif; font-size: 14px;">Phasellus cursus eleifend libero, euismod sodales tellus consectetur sed. Nunc pretium nulla sed lacus consectetur porta. Donec libero sem, volutpat a risus nec, lobortis congue sem. Aliquam vel purus turpis. Quisque tristique erat ut dui sodales, nec sagittis orci semper. Sed sem ex, venenatis sit amet rhoncus vitae, sollicitudin quis est. Sed id placerat neque, in pulvinar dui. Phasellus tristique lacus velit, sit amet sodales magna facilisis aliquet. Donec erat libero, mollis quis risus sit amet, dictum bibendum libero.</p>',
                'product_video' => 'https://www.youtube.com/watch?v=X_6Q38CFYBw',
                'product_weight' => 100,
                'created_at' => NULL,
                'updated_at' => '2018-06-25 19:08:57',
            ),
            1 => 
            array (
                'id_product' => 2,
                'id_product_category' => NULL,
                'product_code' => 'PR-002',
                'product_name' => 'ALMOND BRIGHTENING DAY SERUM',
                'product_description' => 'Diperkaya dengan Almond Oil , Daisy Flower Extract dan Beet Root Extract yang membantu mencerahkan dan melembabkan kulit wajah.',
                'product_video' => NULL,
                'product_weight' => 100,
                'created_at' => NULL,
                'updated_at' => '2018-06-07 14:48:21',
            ),
            2 => 
            array (
                'id_product' => 3,
                'id_product_category' => 3,
                'product_code' => 'PR-003',
                'product_name' => 'TEEN\'S SEBUM CONTROL CREAM',
                'product_description' => 'Mengandung sebum control, vitamin,  Argania Spinosa dan Beet Root Extract yang dapat mengurangi sebum dan menjaga kelembaban kulit wajah. ',
                'product_video' => NULL,
                'product_weight' => 100,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            3 => 
            array (
                'id_product' => 5,
                'id_product_category' => NULL,
                'product_code' => '009a4308-d1ff-11e5-850e-00ff901fd500',
                'product_name' => 'NG NATURAL PEELING FOR ANTI AGING',
                'product_description' => NULL,
                'product_video' => NULL,
                'product_weight' => 80,
                'created_at' => '2018-04-11 16:41:15',
                'updated_at' => '2018-04-11 16:41:15',
            ),
            4 => 
            array (
                'id_product' => 7,
                'id_product_category' => 4,
                'product_code' => 'W001',
                'product_name' => 'UV FILTER MOIST DAY CREAM',
                'product_description' => '<p>Mengandung UV filter yang dapat membantu melindungi kulit wajah dari 
sinar matahari dan menjaga kelembaban kulit wajah anda yang diperkaya 
dengan Shea Butter dan Vitamin E. </p><p>Oleskan krim secara merata ke wajah di pagi hari setelah penggunaan krim pertama.<br><br></p>',
                'product_video' => NULL,
                'product_weight' => 100,
                'created_at' => '2018-04-18 15:45:19',
                'updated_at' => '2018-04-18 15:45:19',
            ),
            5 => 
            array (
                'id_product' => 8,
                'id_product_category' => 4,
                'product_code' => 'W002',
                'product_name' => 'SKIN REJUVENATION COMPLEX',
                'product_description' => '<p>Mengandung peptida, Arnica Extract sebagai &nbsp;anti aging dan vitamin B5 yang membantu melembabkan kulit wajah. </p><p>Oleskan krim secara merata ke wajah di pagi atau malam hari setelah penggunaan krim pertama.<br><br></p>',
                'product_video' => NULL,
                'product_weight' => 100,
                'created_at' => '2018-04-18 15:46:11',
                'updated_at' => '2018-04-18 15:46:11',
            ),
            6 => 
            array (
                'id_product' => 9,
                'id_product_category' => 4,
                'product_code' => 'W003',
                'product_name' => 'RED GINGSENG SERUM',
                'product_description' => '<p>Mengandung Red Ginseng sebagai anti aging dan extract botanical yang dapat  membantu melembabkan kulit wajah.<br></p>',
                'product_video' => NULL,
                'product_weight' => 100,
                'created_at' => '2018-04-18 15:46:42',
                'updated_at' => '2018-04-18 16:06:58',
            ),
            7 => 
            array (
                'id_product' => 10,
                'id_product_category' => 4,
                'product_code' => 'W004',
                'product_name' => 'NIGHT BODY WHITENING CREAM',
                'product_description' => '<p>Mengandung Bellis Perenis Flower Extract dan Peanut Oil yang digunakan 
untuk mencerahkan dan melembabkan kulit badan, tangan dan kaki. </p><p>Oleskan krim di malam hari sebelum tidur secara merata ke badan, tangan, dan kaki. <br><br><br></p>',
                'product_video' => NULL,
                'product_weight' => 100,
                'created_at' => '2018-04-18 15:47:10',
                'updated_at' => '2018-04-18 16:07:32',
            ),
            8 => 
            array (
                'id_product' => 11,
                'id_product_category' => 4,
                'product_code' => 'W005',
                'product_name' => 'MOISTURIZING SKIN TONER',
                'product_description' => '<p>Mengandung Milk Protein yang digunakan untuk membersihkan dan melembabkan pada bagian wajah dan leher.</p><p>Setelah wajah dibersihkan, tuang toner ke kapas, kemudian tepuk-tepuk ke
area wajah dan leher. Hindari kelopak mata bagian atas dan area bibir.<br></p>',
                'product_video' => NULL,
                'product_weight' => 100,
                'created_at' => '2018-04-18 15:47:44',
                'updated_at' => '2018-04-18 16:06:51',
            ),
            9 => 
            array (
                'id_product' => 12,
                'id_product_category' => 4,
                'product_code' => 'W006',
                'product_name' => 'MOISTURIZING NECK CREAM',
                'product_description' => '<p>Mengandung Shea Butter dan Aloe Vera Extract yang membantu mencerahkan, meremajakan dan melembabkan kulit leher anda. </p><p>Oleskan krim ke leher 15 menit sebelum terpapar sinar matahari. Ulangi pemakaian setelah berenang atau berkeringat.<br><br></p>',
                'product_video' => NULL,
                'product_weight' => 100,
                'created_at' => '2018-04-18 15:48:15',
                'updated_at' => '2018-04-18 15:48:15',
            ),
        ));
        
        
    }
}