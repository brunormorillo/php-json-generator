<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$jsonOriginal = json_decode(file_get_contents("php://input"), true);

if (!empty($jsonOriginal)) {
    $return = replaceAll($jsonOriginal);
    http_response_code(200);
    echo json_encode($return);
} else {
    http_response_code(400);
    echo json_encode(["message" => "Nenhum dado recebido."]);
}

function replaceAll($jsonOriginal)
{
    $jsonRepeatReplaced = replaceRepeat($jsonOriginal);
    return replaceOthers($jsonRepeatReplaced[0]);
}

function replaceRepeat($jsonAtual)
{
    $result = [];

    if (gettype($jsonAtual) == 'array') {
        foreach ($jsonAtual as $key => $value) {
            if ($key === "repeat()") {
                $qtd = $value['options']['qtd'];
                $data = $value['data'];
                $result = array_merge($result, repeatJsonData($data, $qtd));
            } elseif (is_array($value)) {
                $result[$key] = replaceRepeat($value);
            } else {
                $result[$key] = $value;
            }
        }
    }

    return $result;
}

function repeatJsonData($data, $qtd)
{
    $result = [];
    for ($i = 1; $i <= $qtd; $i++) {
        $result[] = replaceRepeat($data);
    }
    return $result;
}


function falseOrNull($falsePercentage, $nullPercentage)
{
    $value = true;
    if ($falsePercentage != 0 || $nullPercentage != 0) {
        if ($falsePercentage) {
            if (rand(1, 100) <= $falsePercentage) {
                $value = false;
            }
        }
        if ($nullPercentage) {
            if (rand(1, 100) <= $nullPercentage) {
                $value = null;
            }
        }
    }
    return $value;
}


function replaceOthers($jsonAtual)
{
    $index = 1;
    //Verifica se o JSON é um array, para rodar o foreach dentro dele.
    if (gettype($jsonAtual) == 'array') {
        foreach ($jsonAtual as $key => $value) {
            //Verifica se o item atual é um array, para poder chamar de forma recursiva a função.
            if (is_array($value)) {
                if (isset($value['objectId()'])) {
                    //Caso seja um objectId().
                    $value = generateObjectId($value['objectId()']);
                } elseif (isset($value['integer()'])) {
                    //Caso seja um integer().
                    $value = generateInteger($value['integer()']);
                } elseif (isset($value['boolean()'])) {
                    //Caso seja um boolean().
                    $value = generateBoolean($value['boolean()']);
                } elseif (isset($value['floating()'])) {
                    //Caso seja um floating().
                    $value = generateFloating($value['floating()']);
                } elseif (isset($value['money()'])) {
                    //Caso seja um money().
                    $value = generateMoney($value['money()']);
                } elseif (isset($value['custom()'])) {
                    //Caso seja um custom().
                    $value = selectCustom($value['custom()']);
                } elseif (isset($value['gender()'])) {
                    //Caso seja um custom().
                    $value = selectGender($value['gender()']);
                } elseif (isset($value['company()'])) {
                    //Caso seja um custom().
                    $value = generateCompany($value['company()']);
                } elseif (isset($value['phone()'])) {
                    //Caso seja um custom().
                    $value = generatePhone($value['phone()']);
                }
                $jsonAtual[$key] = replaceOthers($value);
            } else {
                if ($value === 'guid()' || $key === 'guid()') {
                    $jsonAtual[$key] = generateGuid();
                } elseif ($value === 'index()' || $key === 'index()') {
                    $jsonAtual[$key] = $index;
                    $index++;
                } elseif ($value === 'fullName()' || $key === 'fullName()') {
                    $jsonAtual[$key] = generateFullName();
                } elseif ($value === 'firstName()' || $key === 'firstName()') {
                    $jsonAtual[$key] = generateFirstName();
                } elseif ($value === 'surName()' || $key === 'surName()') {
                    $jsonAtual[$key] = generateSurName();
                } elseif ($value === 'email()' || $key === 'email()') {
                    $jsonAtual[$key] = generateEmail();
                }
            }
        }
    }

    return $jsonAtual;
}

function generateInteger($value)
{
    $falsePercentage = ($value['options']['falsePercentage']) ?? 0;
    $nullPercentage = ($value['options']['nullPercentage']) ?? 0;
    $falseOrNull = falseOrNull($falsePercentage, $nullPercentage);
    if (!$falseOrNull) {
        return $falseOrNull;
    }

    $min = ($value['options']['min']) ?? 1;
    $max = ($value['options']['max']) ?? 9;
    if ($min > $max) {
        $min = $max;
    }
    return rand($min, $max);
}

function generateRandomHash($qtd = 1)
{
    //return md5(uniqid(rand(), true));
    return bin2hex(random_bytes($qtd));
}

function generateGuid()
{
    if (function_exists('com_create_guid')) {
        return trim(com_create_guid(), '{}');
    } else {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            rand(0, 0xffff),
            rand(0, 0xffff),
            rand(0, 0xffff),
            rand(0, 0x0fff) | 0x4000,
            rand(0, 0x3fff) | 0x8000,
            rand(0, 0xffff),
            rand(0, 0xffff),
            rand(0, 0xffff)
        );
    }
}

function generateObjectId($value)
{
    $value['options']['qtd'] = ($value['options']['qtd']) ?? 1;

    if (is_array($value['options']['qtd'])) {
        //Caso a qtd seja um array (Ou seja, outra função gerando ela), chama de forma recursiva a função para gerar o valor.
        $value['options']['qtd'] = array_values(replaceOthers($value['options']))[0];
    }

    //Caso o valor seja 0 não é possível gerar um hash, então ele é definido como 1.
    if ($value['options']['qtd'] == 0)
        $value['options']['qtd'] = 1;

    return generateRandomHash($value['options']['qtd']);
}

function generateBoolean($value)
{
    $falsePercentage = ($value['options']['falsePercentage']) ?? 0;
    $nullPercentage = ($value['options']['nullPercentage']) ?? 0;
    $falseOrNull = falseOrNull($falsePercentage, $nullPercentage);
    if (!$falseOrNull) {
        return $falseOrNull;
    }

    return rand(0, 1) === 1;
}

function generateFloating($value)
{
    $falsePercentage = ($value['options']['falsePercentage']) ?? 0;
    $nullPercentage = ($value['options']['nullPercentage']) ?? 0;
    $falseOrNull = falseOrNull($falsePercentage, $nullPercentage);
    if (!$falseOrNull) {
        return $falseOrNull;
    }

    $min = ($value['options']['min']) ?? 1;
    $max = ($value['options']['max']) ?? 9;
    $decimals = ($value['options']['decimals']) ?? 2;
    if ($decimals > 15) {
        $decimals = 15;
    }
    $round = ($value['options']['round']) ?? false;

    $scale = 10 ** $decimals;
    $randomFloat = $min + (rand() / getrandmax()) * ($max - $min);
    $value = round($randomFloat * $scale) / $scale;
    if ($round) {
        $value = round($value);
    }
    return $value;
}

function generateMoney($value)
{
    $falsePercentage = ($value['options']['falsePercentage']) ?? 0;
    $nullPercentage = ($value['options']['nullPercentage']) ?? 0;
    $falseOrNull = falseOrNull($falsePercentage, $nullPercentage);
    if (!$falseOrNull) {
        return $falseOrNull;
    }

    $min = ($value['options']['min']) ?? 1;
    $max = ($value['options']['max']) ?? 9;
    $decimals = ($value['options']['decimals']) ?? 2;
    $prefix = ($value['options']['prefix']) ?? '';
    $separator = ($value['options']['separator']) ?? '';
    $thousand = ($value['options']['thousand']) ?? '';

    $scale = 10 ** $decimals;
    $randomFloat = $min + (rand() / getrandmax()) * ($max - $min);
    $randomFloat = round($randomFloat * $scale) / $scale;

    $formattedFloat = number_format($randomFloat, $decimals, $separator, $thousand);

    return $prefix . $formattedFloat;
}

function generatePhone($value)
{
    $falsePercentage = ($value['options']['falsePercentage']) ?? 0;
    $nullPercentage = ($value['options']['nullPercentage']) ?? 0;
    $falseOrNull = falseOrNull($falsePercentage, $nullPercentage);
    if (!$falseOrNull) {
        return $falseOrNull;
    }

    $ddi = ($value['data']['ddi']) ?? false;
    $ddd = ($value['data']['ddd']) ?? false;
    $phoneNumber = ($value['data']['phoneNumber']) ?? false;

    $ddiLength = ($value['options']['ddiLength']) ?? 2;
    //Define o máximo do DDI.
    $ddiLength = ($ddiLength > 10) ? 9 : $ddiLength;
    //Caso o DDI esteja abaixo do mínimo, define como 2.
    $ddiLength = ($ddiLength != 0) ? $ddiLength : 2;

    $dddLength = ($value['options']['dddLength']) ?? 2;
    //Define o máximo do DDD.
    $dddLength = ($dddLength > 10) ? 9 : $dddLength;
    //Caso o DDD esteja abaixo do mínimo, define como 2.
    $dddLength = ($dddLength != 0) ? $dddLength : 2;

    $plus = ($value['options']['plus']) ?? false;
    $spaceAfterPlus = ($value['options']['spaceAfterPlus']) ?? false;

    $parentheses = ($value['options']['parentheses']) ?? false;
    $spaceAfterParentheses = ($value['options']['spaceAfterParentheses']) ?? false;

    $dash = ($value['options']['dash']) ?? false;
    $dashBefore = ($value['options']['dashBefore']) ?? 4;
    $dash = ($dashBefore == 0) ? false : $dash;
    $spaceAroundDash = ($value['options']['spaceAroundDash']) ? ' - ' : '-';

    $phoneLength = ($value['options']['phoneLength']) ?? 9;
    //Define o máximo do número de telefone.
    $phoneLength = ($phoneLength > 15) ? 15 : $phoneLength;
    //Caso o número de telefone esteja abaixo do mínimo, define como 9.
    $phoneLength = ($phoneLength < 1) ? 9 : $phoneLength;

    if (!$phoneNumber) {
        for ($i = 1; $i <= $phoneLength; $i++) {
            $phoneNumber .= generateInteger([
                'options' => [
                    'min' => 0,
                    'max' => 9
                ]
            ]);
        }
        if ($i == 1 && $phoneNumber == 0) {
            $phoneNumber = 9;
        }

        $strLenPhoneNumber = strlen($phoneNumber);
        //Apenas adiciona o dash caso o número seja maior que o número de caracteres para se colocar o dash.
        if ($dash && $strLenPhoneNumber > $dashBefore) {
            $position = $strLenPhoneNumber - $dashBefore;
            $phoneNumber = substr($phoneNumber, 0, $position) . $spaceAroundDash . substr($phoneNumber, $position);
            //$phoneNumber = substr_replace($phoneNumber, $usarSpaceAroundDash, $position, 0);
        }
    }
    if (!$ddd) {
        for ($i = 1; $i <= $dddLength; $i++) {
            $ddd .= generateInteger([
                'options' => [
                    'min' => 0,
                    'max' => 9
                ]
            ]);
        }
        if ($parentheses) {
            $ddd = '(' . $ddd . ')';
            if ($spaceAfterParentheses) {
                $ddd .= ' ';
            }
        }
        $phoneNumber = $ddd . $phoneNumber;
    }
    if (!$ddi) {
        for ($i = 1; $i <= $ddiLength; $i++) {
            $ddi .= generateInteger([
                'options' => [
                    'min' => 0,
                    'max' => 9
                ]
            ]);
        }
        if ($plus) {
            $ddi = '+' . $ddi;
            if ($spaceAfterPlus) {
                $ddi .= ' ';
            }
        }
        $phoneNumber = $ddi . $phoneNumber;
    }
    return $phoneNumber;
}

function selectCustom($value)
{
    return $value['data'][rand(1, count($value['data']))];
}

function selectGender($value)
{
    $falsePercentage = ($value['options']['falsePercentage']) ?? 0;
    $nullPercentage = ($value['options']['nullPercentage']) ?? 0;
    $falseOrNull = falseOrNull($falsePercentage, $nullPercentage);
    if (!$falseOrNull) {
        return $falseOrNull;
    }

    if (!isset($value['data'])) {
        $value['data'] = [
            '1' => 'Male',
            '2' => 'Femeale',
            '3' => 'Others'
        ];
    }
    return selectCustom($value);
}

function generateFirstName()
{
    $firstName = [
        "Maria", "João", "Ana", "José", "Lucas", "Emily", "Sophia", "William", "Isabella", "Pedro", "Gabriel", "Carlos", "Daniel", "Camila", "Fernanda", "Beatriz", "Clara", "Paulo", "Luiza", "Arthur", "Mateus", "Amanda", "Marcelo", "Eduardo", "Alice", "David", "Mariana", "Ricardo", "Felipe", "Raquel", "Júlia", "Victor", "Catarina", "Isabel", "Luis", "Diego", "Bruno", "Alex", "Vitoria", "Francisco", "Rafael", "Samantha", "Sofia", "Henrique", "Adriana", "Roberto", "Miguel", "Elena", "Marcos", "Juan", "Leonardo", "Thomas", "Samuel", "Carolina", "Cristina", "Igor", "Marina", "Lara", "Patricia", "Sara", "Natalia", "Fábio", "Juliana", "Matthew", "Sarah", "Rodrigo", "Aline", "Flávia", "Letícia", "Adriano", "Grace", "Augusto", "Regina", "Diana", "Isaac", "Gustavo", "Viviane", "Tiago", "Fernando", "Michelle", "Tatiana", "Otávio", "Nina", "Simone", "Jorge", "Giovanna", "Laura", "Ariana", "Luciana", "Elaine", "Maya", "Leila", "Filipe", "Ester", "Benjamin", "Inês", "Marco", "Yasmin", "Adam", "Ian", "Kelly", "Julia", "Rosa", "Alexandra", "Leandro", "Chris", "Nelson", "Vinícius", "Daniele", "Nathalia", "Erick", "Lucia", "Silvia", "Roger", "Jake", "Sérgio", "Paola", "Gisele", "Antonio", "Rita", "Cecilia", "Xavier", "Marta", "Caio", "Hugo", "Bianca", "Fiona", "Jonathan", "Jason", "Mauro", "Olivia", "Emma", "Valentina", "Leo", "Larissa", "Andrea", "Sophie", "Kyle", "Renata", "Esther", "Alana", "Bernardo", "Elisa", "Paula", "Taylor", "Peter", "Tony", "Ivan", "Angela", "Zoe", "George", "Joana", "Rafaela", "Irene", "Edgar", "Nancy", "Vera", "Martin", "Aaron", "Oscar", "Violet", "Celine", "Nick", "Ryan", "Lia", "Cassandra", "Madison", "Jesse", "Vivian", "Teresa", "Ellen", "Bryan", "Melissa", "Ivy", "Blake", "Lily", "Albert", "Noah", "Isa", "Jade", "Carmen", "Keith", "Nora", "Louise", "Sean", "Dylan", "Skyler", "Gloria", "Luke", "Eva", "Joy", "Jordan", "Andre", "Cristiano", "Faye", "Cole", "Andres", "Charlotte", "Zara", "Denise", "Beverly", "Mila", "Sam", "Jean", "Kylie", "Justin", "Wesley", "Claudia", "Bruce", "Alberto", "Gwen", "Owen", "Hannah", "Eric", "Henry", "Lorraine", "Philip", "Molly", "Saul", "Jackie", "Elliot", "Michele", "Max", "Wanda", "Mauricio", "Gael", "Isadora", "Lena", "Eleanor", "Graham", "Kirk", "Allan", "Valeria", "Mabel", "Abigail", "Neil", "Omar", "Flora", "Deborah", "Eugene", "Leticia", "Lana", "Hazel", "Riley", "Oliver", "Cody", "Ashley", "Alan", "Javier", "Silas", "Vivienne", "Isla", "Walter", "Scott", "Gene", "Dennis", "Evelyn", "Daisy", "Rex", "Ramona", "Tiffany", "Nathaniel", "Roy", "Olive", "Marie", "Pearl", "Ray", "Carl", "Pamela", "Penelope", "Mandy", "Stephanie", "Eliza", "Rosie", "Kayla", "Eve", "Edith", "Vanessa", "Juliet", "Mae", "Andy", "Renee", "June", "Agnes", "Harold", "Lola", "Stanley", "Caleb", "Rosemary", "Lester", "Priscilla", "Armando", "Clifford", "Rebecca", "Vicki", "Winnie", "Myra", "Lydia", "Kathryn", "Floyd", "Veronica", "Jill", "Monica", "Tina", "Ben", "Jon", "Isaiah", "Todd", "Marion", "Cynthia", "Kent", "Lyle", "Sheila", "Kathy", "Shirley", "Sylvia", "Sandy", "Cheryl", "Sonia", "Mercedes", "Dorothy", "Joel", "Ismael", "Claire", "Colin", "Helen", "Nigel", "Curtis", "Darren", "Morgan", "Norman", "Ruby", "Cara", "Troy", "Jasmine", "Travis", "Clyde", "Penny", "Vicky", "Randy", "Eunice", "Lillian", "Trent", "Dale", "Perry", "Daryl", "Ellis", "Leigh", "Enrique", "Manny", "Terri", "Brad", "Jeanne", "Lauren", "Shane", "Colleen", "Terry", "Lindsey", "Robin", "Neal", "Cecil", "Anita", "Beth", "Brett", "Garry", "Conrad", "Greg", "Lyndon", "Dwight", "Iris", "Bernadette", "Janet", "Grant", "Quincy", "Roland", "Brent", "Stuart", "Audrey", "Raul", "Candace", "Mack", "Debbie", "Maureen", "Freddie", "Cora", "Edwin", "Russell", "Quinn", "Spencer", "Rhonda", "Cedric", "Vernon", "Arturo", "Lou", "Olga", "Darnell", "Viola", "Naomi", "Wilma", "Benny", "Bridget", "Nadine", "Elmer", "Lonnie", "Angelo", "Becky", "Geneva", "Kerry", "Tabitha", "Alfredo", "Billie", "Loretta", "Miriam", "Laurie", "Janice", "Constance", "Wes", "Belinda", "Duane", "Gerard", "Trina", "Delia", "Suzette", "Milton", "Melody", "Patsy", "Reginald", "Sylvester", "Elias", "Wendy", "Harvey", "Ollie", "Harrison", "Rose", "Marshall", "Clark", "Rosalind", "Charlene", "Amos", "Tommy", "Elton", "Gina", "Sherri", "Roderick", "Misty", "Nell", "Warren", "Muriel", "Lynn", "Ginger", "Donna", "Carla", "Eloise", "Dixie", "Phyllis", "Lynne", "Roscoe", "Kim", "Polly", "Ned", "Dora", "Rod", "Rachael", "Carole", "Maxine", "Franklin", "Guy", "Cleo", "Cornelius", "Dina", "Bert", "Dewayne", "Mona", "Melba", "Seth", "Edna", "Sybil", "Dolores", "Doris", "Harriet", "Glen", "Rosetta", "Hattie", "Bertha", "Leona", "Hope", "Ira", "Willis", "Minnie", "Eula", "Wade", "Meredith", "Pearlie", "Elvira", "Jeannie", "Alton", "Delbert", "Clarence", "Luther", "Nellie", "Effie", "Wallace", "Isiah", "Genevieve", "Adele", "Beulah", "Blanche", "Iva", "Gertie", "Gwendolyn", "Homer", "Myrtle", "Elliott", "Percy", "Rufus", "Chester", "Cecelia", "Elnora", "Fannie", "Lucille", "Mattie", "Flossie", "Lila", "Maggie", "Adelaide", "Mable", "Stella", "Hollie", "Lyman", "Ida", "Fern", "Susie", "Gladys", "Alma", "Opal", "Sally", "Ruben", "Pete", "Rosalie", "Tillie", "Hester", "Addie", "Lottie", "Lela", "Johnnie", "Maude", "Agatha", "Geraldine", "Lulu", "Neva", "Frieda", "Aurelia", "Goldie", "Lenora", "Nelle", "Celia", "Maud", "Verna", "Inez", "Elma", "Luella", "Della", "Thelma", "Henrietta", "Ora", "Cornelia", "Willa", "Ethel", "Adeline", "Lina", "Zella", "Ina", "Lelia", "Josie", "Harriett", "Essie", "Sue", "Sadie", "Johanna", "Mina", "Kitty", "Birdie", "Lillie", "Lizzie", "Mollie", "Elva", "Avis", "Louisa", "Eugenia", "Maudie", "Hanna", "Florine", "Dolly", "Pauline", "Louella", "Adela", "Gussie", "Nettie", "Freda", "Lucile", "Alta", "Marian", "Janie", "Marguerite", "Zora", "Leora", "Jennie", "Fanny", "Edythe", "Etta", "Sallie", "Myrtie", "Mamie", "Theresa", "Ada", "Zula", "Winifred", "Madge", "Ola", "Lucy", "Susan", "Barbara", "Amelia", "Nola", "Lorena", "Ophelia", "Kathleen", "Lettie", "Roxie", "Estelle", "Millie", "Katharine", "Ella", "Elsie", "Annie", "Hilda", "Josephine", "Bessie", "Isabelle", "Anastasia", "Dulce", "Hernan", "Agustin", "Julio", "Cesar", "Ignacio", "Vicente", "Emilio", "Alejandro", "Manuel", "Guillermo", "Jose", "Sergio", "Lorenzo", "Sebastian", "Gerardo", "Esteban", "Adolfo", "Julian", "Benito", "Ramiro", "Gonzalo", "Joaquin", "Mario", "Alvaro", "Felix", "Horacio", "Juanita", "Ines", "Alba", "Susana", "Alicia", "Lourdes", "Juana", "Rocio", "Martha", "Norma", "Luz", "Magdalena", "Esperanza", "Blanca", "Rebeca", "Fabiola", "Matilde", "Yolanda", "Graciela", "Frida", "Antonia", "Amalia", "Isidora", "Soledad", "Concepcion", "Rosalinda", "Angeles", "Elsa", "Martina", "Violeta", "Rosario", "Victoria", "Gabriela", "Agustina", "Estela", "Margarita", "Carlota", "Alejandra", "Sol", "Liliana", "Inmaculada", "Felisa", "Maribel", "Luisa", "Anahi", "Araceli", "Azucena", "Georgina", "Hortensia", "Julieta", "Rosalba", "Delfina", "Celeste", "Macarena", "Bernarda", "Paloma", "Guadalupe", "Ruth", "Beatrice", "Mildred", "Virginia", "Marjorie", "Margaret", "Florence", "Caroline", "Catherine", "Lula", "Gertrude", "Katherine", "Lucinda", "Jessie", "Jane", "Estella", "Bess", "Georgia", "Betty", "Frances", "Katie", "Alberta", "Mary", "Matilda", "Madeline", "Phoebe", "Bonnie", "May", "Anne"
    ];
    return $firstName[rand(0, count($firstName) - 1)];
}

function generateSurName()
{
    $surName = [
        "Silva", "Smith", "Johnson", "Garcia", "Rodriguez", "Martinez", "Williams", "Brown", "Jones", "Pereira", "Hernandez", "Lee", "Gonzalez", "Perez", "Lopez", "Murphy", "Anderson", "Costa", "Kim", "Davis", "Wilson", "Taylor", "Thomas", "Moore", "Santos", "Clark", "Thompson", "Lima", "Sanchez", "Harris", "Nelson", "Evans", "Adams", "Scott", "Cook", "Bailey", "Fernandes", "Hall", "Campbell", "Mitchell", "Roberts", "Young", "Gomes", "Wright", "Martins", "Hill", "Green", "King", "Carter", "Fisher", "Ribeiro", "Turner", "Phillips", "Allen", "Torres", "Parker", "Collins", "Ramirez", "Almeida", "Freitas", "Morris", "Hughes", "Reed", "Flores", "Edwards", "Kelly", "Howard", "Olson", "Cooper", "Ferreira", "Jenkins", "Ross", "Simmons", "Diaz", "Powell", "Graham", "Rogers", "Ward", "James", "Foster", "Barnes", "Bell", "Murray", "Moreira", "Rivera", "Morgan", "Stevens", "Meyer", "Wallace", "Mello", "Ramos", "Woods", "Long", "Ford", "Chen", "Price", "Watson", "Butler", "Jensen", "Bennett", "Reyes", "Wells", "Castro", "Coelho", "Perry", "Peterson", "West", "Hunt", "Stewart", "Fields", "Hoffman", "Gibson", "Gray", "Marques", "Ruiz", "Vasquez", "Daniels", "Harper", "Arnold", "Schmidt", "Boyd", "Warren", "Medeiros", "Fox", "Jordan", "Hayes", "Harvey", "Beck", "Cole", "Black", "Hunter", "Webb", "Guerra", "Morrison", "Ryan", "Carvalho", "Baker", "Vargas", "Oliveira", "Cruz", "Dunn", "Gutierrez", "Mills", "Nguyen", "Matthews", "Alexander", "Spencer", "Sullivan", "Shaw", "Lambert", "Weaver", "Reid", "Bishop", "Fowler", "Nogueira", "Knight", "Gilbert", "Rhodes", "Day", "Simons", "Lawson", "Ortiz", "Jennings", "Wheeler", "Romero", "Dixon", "Dean", "Cunningham", "Snyder", "Schneider", "Saunders", "Byrne", "Douglas", "Monteiro", "Santiago", "Carpenter", "Franklin", "Frazier", "Armstrong", "Gordon", "Mcdonald", "Patterson", "Harrison", "Rose", "Machado", "Barrett", "Lawrence", "Elliott", "Jacobs", "Stevenson", "Vieira", "Porter", "Maxwell", "Craig", "Cohen", "Hansen", "Keller", "Neal", "Klein", "Bradley", "Mendes", "Page", "Parsons", "Marsh", "Boone", "Hale", "Curry", "Lynch", "Lowell", "Nash", "Mueller", "Erickson", "Barros", "Mccoy", "May", "Caldwell", "Leon", "Poole", "Borges", "Atkinson", "Fuller", "Christensen", "Casey", "Frank", "Sharp", "Freeman", "Tucker", "Hawkins", "Nichols", "Glover", "Cameron", "Shepherd", "Mckinney", "Barbosa", "Wolf", "Hoover", "Finch", "Lowe", "Wilkins", "Goodman", "Rice", "Frye", "Norton", "Mckay", "Barker", "Miles", "Crawford", "Norris", "Griffin", "Blair", "Bowers", "Baxter", "Mann", "Booth", "Clarke", "Stephens", "Brady", "Welch", "Brewer", "Solomon", "Pena", "Mcgrath", "Ingram", "Forbes", "Schwartz", "Combs", "Winters", "Dickson", "Nunes", "Clements", "Noble", "Vega", "Cooke", "Bates", "Branch", "Meier", "Huff", "Wong", "Dudley", "Mckenzie", "Moss", "Orr", "Conway", "Newman", "Stokes", "Randall", "Cline", "Hobbs", "Pratt", "Sherman", "Macias", "Monroe", "Hays", "Holt", "Barton", "Blanchard", "Dalton", "Crane", "Pugh", "Guimaraes", "Mccarthy", "Hardy", "Mcclain", "Whitney", "Powers", "Buckley", "Fitzgerald", "Sims", "Collier", "Bruce", "Chambers", "Eaton", "Sloan", "York", "Cortez", "Mclean", "Conner", "Livingston", "Nielsen", "Braun", "Todd", "Sutton", "Kirk", "Burnett", "Kramer", "Graves", "Hodge", "Lyons", "Baldwin", "Araujo", "Parks", "Mcdowell", "Flynn", "Marks", "Munoz", "Donaldson", "Carson", "Gould", "Villanueva", "Preston", "Hines", "Mcmahon", "Stuart", "Estrada", "Wiggins", "Gallagher", "Key", "Bass", "Gallardo", "Osborne", "Madden", "Bean", "Tate", "Kaufman", "Friedman", "Haley", "Davies", "Brock", "Osorio", "Stein", "Farrell", "Mercer", "Glenn", "Lucas", "Bridges", "Short", "Serrano", "Waller", "Mcclure", "Carrillo", "Morrow", "Christian", "Pickett", "Duffy", "Briggs", "Hatfield", "Bowen", "Calderon", "Burgess", "Pollard", "Oneil", "Skinner", "Avery", "Bright", "Underwood", "Cash", "Savage", "Novak", "Bryant", "Wilder", "Buck", "Munro", "Pittman", "Wu", "Humphrey", "Leblanc", "Fuentes", "Dailey", "Kemp", "Mcintyre", "Lutz", "Archer", "Hutchinson", "Sweeney", "Ho", "Joyce", "Merritt", "Chase", "Benson", "Mcneil", "Jewell", "Maddox", "Forrest", "Church", "Vaughan", "Wilkinson", "Landry", "Clayton", "Middleton", "Fry", "Davila", "Rivas", "Mack", "Cochran", "Lang", "Mcguire", "Zimmerman", "Dillard", "Pham", "Shea", "Roy", "Conrad", "Melton", "Vance", "Rocha", "Mcgee", "Beasley", "Finley", "Prince", "Hutchins", "Hammond", "Swanson", "Mejia", "Valenzuela", "Walton", "Mayer", "Barlow", "Rich", "Cisneros", "Reilly", "Benjamin", "Levy", "Schultz", "Drake", "Potter", "Potts", "Robinson", "Holmes", "Kent", "Blackburn", "Compton", "Koch", "Bartlett", "Gallegos", "Fleming", "Shields", "Mcintosh", "Bray", "Sherwood", "Wyatt", "Farmer", "Cantrell", "Justice", "Moses", "Lott", "Beard", "Small", "Meadows", "Colvin", "Rowland", "Best", "Proctor", "Bradshaw", "Glass", "Decker", "Stanton", "Sweet", "Donovan", "Gamble", "Krause", "Nolan", "Boucher", "Travis", "Luna", "Roth", "Pope", "Vogel", "Boyce", "Dorsey", "Downs", "Mays", "Waters", "Nixon", "House", "Kaiser", "Garrison", "Duran", "Hampton", "Dougherty", "Fraser", "Holder", "Trevino", "English", "Gates", "Quinn", "Navarro", "Valentine", "Wilkerson", "Shelton", "Cherry", "Wolfe", "Prado", "Vang", "Cowan", "Vazquez", "Bond", "Pace", "Frost", "Lake", "Carney", "Levine", "Massey", "Dejesus", "Paul", "Sparks", "Alves", "Cabrera", "Webster", "Britton", "Hull", "Burch", "Russell", "Correa", "Puckett", "Coffey", "Woodward", "Hewitt", "Delgado", "Larsen", "Mcknight", "Whitley", "Dillon", "Cardenas", "Raymond", "Boyle", "Foley", "Ewing", "Hinton", "Walls", "Barrera", "Horne", "Olsen", "Sykes", "Riggs", "Zamora", "Abbott", "Gill", "Valdez", "Schaefer", "Gilmore", "Whitfield", "Knapp", "Mahoney", "Mcfarland", "Becker", "Rosario", "Weeks", "Franco", "Brennan", "David", "Mcmillan", "Fitzpatrick", "Oconnor", "Blankenship", "Witt", "Summers", "Dotson", "Russo", "Case", "Duke", "Hebert", "Ochoa", "Griffith", "Giles", "Mcgowan", "Ellison", "Barry", "Holcomb", "Vaughn", "Merrill", "Bonner", "Hess", "Moon", "Wang", "Mcdaniel", "Herring", "Dunlap", "Leach", "Mullen", "Browning", "Stark", "Randolph", "Kirkland", "Reeves", "Cote", "Walsh", "Stout", "Oneal", "Figueroa", "Hensley", "Heath", "Conley", "Rowe", "Allison", "Grimes", "Parrish", "Hodges", "Nunez", "Bauer", "Morse", "Huffman", "Chavez", "Sampson", "Weber", "Pruitt", "Deleon", "Santana", "Mullins", "Barber", "Cervantes", "Aguilar", "Cantu", "Robles"
    ];
    return $surName[rand(0, count($surName) - 1)];
}

function generateFullName()
{
    return generateFirstName() . ' ' . generateSurName();
}

function generateCompany($value)
{
    $toUpperCase = ($value['options']['toUpperCase()']) ?? false;
    $toLowerCase = ($value['options']['toLowerCase()']) ?? false;
    $capitalize = ($value['options']['capitalize()']) ?? false;
    $camelCase = ($value['options']['camelCase()']) ?? false;
    $slugify = ($value['options']['slugify()']) ?? false;

    $companyName = [
        "Morillos Eirelli Ltda. ME", "Loja de roupas da Debinha", "Tech Solutions Inc.", "Bela Flor Garden Center", "GreenTech Innovations", "Acme Corporation", "Sunset Electronics", "Gourmet Delights Catering", "OceanView Resorts", "Swift Logistics Group", "Global Marketing Solutions", "Express Auto Repair", "SilverLine Technologies", "HealthWise Pharmacy", "Golden Gate Consulting", "Peak Performance Fitness", "EcoFriendly Builders", "FirstClass Travel Agency", "Smart Data Analytics", "Pristine Cleaning Services", "Alpha Omega Investments", "BlueSky Adventures", "Evergreen Landscaping", "Infinite Horizons Software", "Dynamic Designs Studio", "Crystal Clear Water Solutions", "Urban Elegance Boutique", "Trinity Construction Group", "Sunrise Solar Energy", "Luxury Living Real Estate", "Starlight Entertainment", "Pacific Coast Imports", "Serenity Spa & Wellness", "MountainView Winery", "Quantum Technology Labs", "Timeless Treasures Antiques", "Green Thumb Landscapes", "Seaside Vacation Rentals", "Harmony Health Clinic", "Crimson Creative Agency", "Nature's Harmony Organic Foods", "Empire Builders Group", "Elite Event Planning", "Opulent Jewelry Creations", "Fusion Fitness Studio", "Azure Architecture & Design", "Dreamscape Travel Adventures", "Majestic Marketing Agency", "Royal Realty Group", "Silver Lining Financial Services", "Sunrise Bakery & Cafe", "Visionary Video Productions", "Creative Canvas Art Gallery", "Summit Strategies Consulting", "Tropical Paradise Vacation Rentals", "TechWizards IT Solutions", "Golden Harvest Farm", "Horizon Horizon Realty", "EcoTech Solutions", "Nature's Bounty Health Foods", "Skyline Roofing Contractors", "Rising Sun Construction", "Emerald Isle Resorts", "Wildflower Wellness Center", "Quantum Leap Software", "Everest Adventure Tours", "Crystal Clear Home Inspections", "Elite Elegance Bridal Boutique", "Stratosphere Aerospace Engineering", "Coastal Breeze Real Estate", "Pinnacle Performance Coaching", "Radiant Smiles Dentistry", "Harmony Yoga Studio", "Crimson Rose Florist", "Cityscape Architecture Group", "Golden Oak Financial Advisors", "Sapphire Skies Aviation", "Summit Fitness Center", "Horizon Tech Solutions", "Palm Paradise Resorts", "Emerald City Coffee Roasters", "Sunset Harbor Marina", "Nature's Canvas Art Studio", "Eagle Eye Surveillance", "Blue Wave Marketing", "Solaris Solar Panels", "Mountain Peak Hiking Tours", "Harmony Haven Assisted Living", "Silver Creek Winery", "Sunflower Seed Co-op", "Elite Edge Web Design", "Nova Tech Innovations", "AquaLux Pools", "Cityscape Realty", "Quantum Quilts", "Golden Meadows Retirement Community", "Crimson Leaf Legal Services", "Starfish Swim School", "Horizon Horizon Insurance", "Sky High Drone Services", "Sunrise Sushi Bar", "Terra Nova Landscapes", "EcoLuxe Fashion Boutique", "Pinnacle Properties Management", "Radiant Beauty Salon", "Harborview Estates", "Sapphire Star Jewelry", "Summit Financial Planning", "OceanFront Cafe", "Nature's Touch Massage Therapy", "Silver Stream Productions", "Sunset Ridge Golf Club", "Elite Express Couriers", "NovaStar Software Solutions", "AquaVista Aquariums", "CityScape Consulting Group", "Quantum Mechanics Auto Repair", "Golden Sands Beach Resort", "Crimson Ridge Realty", "Starstruck Entertainment", "Horizon Haven Bed and Breakfast", "Skyline View Landscaping", "Sunrise Ski Rentals", "TerraFirma Earth Sciences", "EcoLiving Home Decor", "Pinnacle Printing Services", "Radiant Health Chiropractic", "Harbor Lights Marina", "Sapphire Waters Spa", "Summit Sports Gear", "OceanView Travel Agency", "Nature's Wisdom Books", "Silver Lining Law Firm", "Sunset Serenity Yoga", "Elite Innovations Labs", "NovaTech Consulting", "AquaBlast Pressure Washing", "CityScape Architects", "Quantum Fitness Equipment", "Golden Meadows Pet Care", "Crimson Leaf Accounting", "Starstruck Photography", "Horizon Heights Apartments", "Skyline View Roofing", "Sunrise Snack Bar", "TerraNova Environmental Solutions", "EcoVenture Outdoor Adventures", "Pinnacle Properties Investments", "Radiant Skincare Clinic", "Harbor Haven Retirement Community", "Sapphire Seas Cruises", "Summit Creative Studios", "OceanFront Vacation Rentals", "Nature's Oasis Herbal Remedies", "Silver Screen Productions", "Sunset Shades Window Tinting", "Elite Edge Marketing", "NovaStar Security Solutions", "AquaGardens Landscaping", "CityScape Real Estate", "Quantum Music Academy", "Golden Sands Surf Shop", "Crimson Leaf Consulting", "Starstruck Event Planning", "Horizon Horizons Travel Agency", "Skyline View Plumbing", "Sunrise Bakery", "TerraNova Adventure Tours", "EcoVista Organic Market", "Pinnacle Properties Rentals", "Radiant Realty", "Harborview Apartments", "Sapphire Dreams Jewelry", "Summit Auto Repair", "OceanView Accounting Services", "Nature's Essence Spa", "Silver Surf Internet Cafe", "Sunset Sails Charter", "Elite Innovations Software", "NovaTech Robotics", "AquaWave Pool Services", "CityScape Law Firm", "Quantum Motorsports", "Golden Meadows Equestrian Center", "Crimson Leaf Marketing", "Starstruck Productions", "Horizon Heights Senior Living", "Skyline View Pest Control", "Sunrise Cafe & Bistro", "TerraNova Landscape Design", "EcoWise Eco-Friendly Products", "Pinnacle Plumbing", "Radiant Dental Care", "Harborview Realty Group", "Sapphire Skies Aviation", "Summit Auto Sales", "OceanView Web Design", "Nature's Best Organic Market", "Silver Stream Video Productions", "Sunset Serenity Spa", "Elite Edge Accounting", "NovaStar Web Development", "AquaLife Aquarium Services", "CityScape Marketing", "Quantum Dynamics Engineering", "Golden Sands Water Sports", "Crimson Leaf Events", "Starstruck Music Academy", "Horizon Heights Property Management", "Skyline View Painting", "Sunrise Sweets Bakery", "TerraNova Construction", "EcoTrend Eco-Friendly Fashion", "Pinnacle Pest Control", "Radiant Salon & Spa", "Harborview Property Rentals", "Sapphire Star Realty", "Summit Accounting Services", "OceanView Travel Tours", "Nature's Beauty Boutique", "Silver Surf Computer Repair", "Sunset Serenity Wellness Center", "Elite Innovations Graphic Design", "NovaTech Mobile Apps", "AquaCare Pool Maintenance", "CityScape Event Planning", "Quantum Quest Adventure Tours", "Golden Meadows Wedding Venue", "Crimson Leaf Financial Services", "Starstruck Photography Studios", "Horizon Heights Apartments", "Skyline View Roofing", "Sunrise Snack Bar", "TerraNova Environmental Solutions", "EcoVenture Outdoor Adventures", "Pinnacle Properties Investments", "Radiant Skincare Clinic", "Harbor Haven Retirement Community", "Sapphire Seas Cruises", "Summit Creative Studios", "OceanFront Vacation Rentals", "Nature's Oasis Herbal Remedies", "Silver Screen Productions", "Sunset Shades Window Tinting", "Elite Edge Marketing", "NovaStar Security Solutions", "AquaGardens Landscaping", "CityScape Real Estate", "Quantum Music Academy", "Golden Sands Surf Shop", "Crimson Leaf Consulting", "Starstruck Event Planning", "Horizon Horizons Travel Agency", "Skyline View Plumbing", "Sunrise Bakery", "TerraNova Adventure Tours", "EcoVista Organic Market", "Pinnacle Properties Rentals", "Radiant Realty", "Harborview Apartments", "Sapphire Dreams Jewelry", "Summit Auto Repair", "OceanView Accounting Services", "Nature's Essence Spa", "Silver Surf Internet Cafe", "Sunset Sails Charter", "Elite Innovations Software", "NovaTech Robotics", "AquaWave Pool Services", "CityScape Law Firm", "Quantum Motorsports", "Golden Meadows Equestrian Center", "Crimson Leaf Marketing", "Starstruck Productions", "Horizon Heights Senior Living", "Skyline View Pest Control", "Sunrise Cafe & Bistro", "TerraNova Landscape Design", "EcoWise Eco-Friendly Products", "Pinnacle Plumbing", "Radiant Dental Care", "Harborview Realty Group", "Sapphire Skies Aviation", "Summit Auto Sales", "OceanView Web Design", "Nature's Best Organic Market", "Silver Stream Video Productions", "Sunset Serenity Spa", "Elite Edge Accounting", "NovaStar Web Development", "AquaLife Aquarium Services", "CityScape Marketing", "Quantum Dynamics Engineering", "Golden Sands Water Sports", "Crimson Leaf Events", "Starstruck Music Academy", "Horizon Heights Property Management", "Skyline View Painting", "Sunrise Sweets Bakery", "TerraNova Construction", "EcoTrend Eco-Friendly Fashion", "Pinnacle Pest Control", "Radiant Salon & Spa", "Harborview Property Rentals", "Sapphire Star Realty", "Summit Accounting Services", "OceanView Travel Tours", "Nature's Beauty Boutique", "Silver Surf Computer Repair", "Sunset Serenity Wellness Center", "Elite Innovations Graphic Design", "NovaTech Mobile Apps", "AquaCare Pool Maintenance", "CityScape Event Planning", "Quantum Quest Adventure Tours", "Golden Meadows Wedding Venue", "Crimson Leaf Financial Services", "Starstruck Photography Studios"
    ];
    $companySelected = $companyName[rand(0, count($companyName) - 1)];
    if ($toUpperCase) {
        $companySelected = strtoupper($companySelected);
    } elseif ($toLowerCase) {
        $companySelected = strtolower($companySelected);
    } elseif ($capitalize) {
        $companySelected = ucwords($companySelected);
    } elseif ($camelCase) {
        $companySelected = lcfirst(str_replace(" ", "", ucwords(str_replace(".", " ", $companySelected))));
    } elseif ($slugify) {
        //Slugify deve ter opções. https://www.npmjs.com/package/slugify
        $companySelected = strtolower(str_replace(" ", "-", $companySelected));
    }
    return $companySelected;
}

function generateEmailDomain()
{
    $emailDomain = [
        // Domínios Internacionais
        "gmail.com", "yahoo.com", "hotmail.com", "aol.com", "outlook.com", "icloud.com", "mail.ru", "yandex.ru", "zoho.com", "protonmail.com", "msn.com", "live.com", "comcast.net", "sbcglobal.net", "verizon.net", "ymail.com", "me.com", "gmx.com", "fastmail.com", "web.de", "hushmail.com", "inbox.com", "rediffmail.com", "rocketmail.com", "earthlink.net", "mail.com", "excite.com", "cox.net", "juno.com", "mindspring.com", "laposte.net", "blueyonder.co.uk", "shaw.ca", "ntlworld.com", "sympatico.ca", "lycos.com",
        // Domínios Franceses
        "orange.fr", "free.fr", "wanadoo.fr",
        // Domínios do Reino Unido
        "hotmail.co.uk", "yahoo.co.uk", "btinternet.com", "virginmedia.com", "talktalk.net",
        // Domínios Alemães
        "t-online.de", "web.de", "gmx.de", "freenet.de",
        // Domínios Italianos
        "alice.it", "libero.it", "virgilio.it", "tiscali.it",
        // Domínios Espanhóis
        "hotmail.es", "yahoo.es",
        // Domínios Brasileiros
        "hotmail.com.br", "bol.com.br", "terra.com.br", "uol.com.br", "ig.com.br",
        // Domínios Asiáticos
        "qq.com", "163.com", "126.com", "sina.com", "sohu.com", "yeah.net", "yahoo.co.jp", "naver.com", "hanmail.net", "daum.net", "korea.com",
        // Outros Domínios Europeus
        "seznam.cz", "centrum.cz", "mail.bg", "abv.bg", "rambler.ru", "ukr.net",
        // Outros
        "optonline.net", "telus.net", "rogers.com", "xtra.co.nz", "bigpond.com", "optusnet.com.au", "telstra.com", "iinet.net.au",
        // Domínios Empresariais (exemplos, estes podem variar muito)
        "ibm.com", "microsoft.com", "google.com", "amazon.com"
    ];
    return $emailDomain[rand(0, count($emailDomain) - 1)];
}

function generateEmailName()
{
    return strtolower(str_replace(" ", ".", generateFullName()));
}

function generateEmail()
{
    return generateEmailName() . "@" . generateEmailDomain();
}
