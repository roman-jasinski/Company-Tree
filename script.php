<?php
class Travel
{
	public $companyId;
    public $price;

    public function __construct($companyId, $price)
    {
        $this->companyId = $companyId;
        $this->price = $price;
    }
}
class Company
{
    public $id;
    public $createdAt;
    public $name;
    public $parentId;
    public $cost = 0;
    public $children = [];

    public function __construct($id, $createdAt, $name, $parentId)
    {
        $this->id = $id;
        $this->createdAt = $createdAt;
        $this->name = $name;
        $this->parentId = $parentId;
    }

    public function addChild($child)
    {
        $this->children[] = $child;
    }

    public function addCost($cost)
    {
        $this->cost += $cost;
    }
}
class TestScript
{
    private $companies = [];
    private $travels = [];
    private $companyListEndPoint = 'https://5f27781bf5d27e001612e057.mockapi.io/webprovise/companies';
    private $travelListEndPoint = 'https://5f27781bf5d27e001612e057.mockapi.io/webprovise/travels';

    public function __construct()
    {
        $this->companies = $this->fetchCompanies();
        $this->travels = $this->fetchTravels();
    }

	private function fetchCompanies()
	{
	    $json = file_get_contents($this->companyListEndPoint);
	    return json_decode($json, true);
	}

	private function fetchTravels()
	{
	    $json = file_get_contents($this->travelListEndPoint);
	    return json_decode($json, true);
	}

    public function execute()
    {
        $start = microtime(true);

        $companyMap = [];
        foreach ($this->companies as $company) {
            $companyObj = new Company($company['id'], $company['createdAt'], $company['name'], $company['parentId']);
            $companyMap[$company['id']] = $companyObj;
        }

        $travelCosts = [];
        foreach ($this->travels as $travel) {
            if (!isset($travelCosts[$travel['companyId']])) {
                $travelCosts[$travel['companyId']] = 0;
            }
            $travelCosts[$travel['companyId']] += (float)$travel['price'];
        }

        foreach ($companyMap as $company) {
            if (isset($travelCosts[$company->id])) {
                $company->addCost($travelCosts[$company->id]);
            }
        }

        foreach ($companyMap as $id => $company) {
            if ($company->parentId !== '0' && isset($companyMap[$company->parentId])) {
                $companyMap[$company->parentId]->addChild($company);
                $parent = $companyMap[$company->parentId];
                while ($parent) {
                    $parent->addCost($company->cost);
                    $parent = isset($companyMap[$parent->parentId]) ? $companyMap[$parent->parentId] : null;
                }
            }
        }

        $result = array_filter($companyMap, function ($company) {
            return $company->parentId === '0';
        });

        echo json_encode(array_values($result));
        echo 'Total time: ' . (microtime(true) - $start);
    }
}
(new TestScript())->execute();