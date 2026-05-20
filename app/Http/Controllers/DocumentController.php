<?php

namespace App\Http\Controllers;

use App\Actions\GenerateRuralActivityAction;
use App\Actions\GenerateAutoDeclarationAction;
use App\Actions\GeneratePresidentDeclarationAction;
use App\Actions\GenerateInsuranceAuthorizationAction;
use App\Actions\GeneratePrevidenceAuthorizationAction;
use App\Actions\GenerateLicenceRequirementAction;
use App\Actions\GenerateResidenceDeclarationAction;
use App\Actions\GenerateAffiliationDeclarationAction;
use App\Actions\GenerateRegistrationFormAction;
use App\Actions\GenerateSecondViaReceiptAction;
use App\Actions\GenerateSocialSecurityGuideAction;
use App\Actions\GenerateINSSRepresentationAction;
use App\Actions\GenerateDisseminationAction;
use App\Actions\GenerateIncomeDeclarationAction;
use App\Actions\GenerateOwnResidenceAction;
use App\Actions\GenerateThirdResidenceAction;
use App\Actions\GenerateNewResidenceAction;
use App\Actions\GenerateSecondCheckAction;
use App\Actions\GeneratePISAction;
use App\Actions\GenerateNonLiterateAffiliationAction;
use App\Services\DocumentGeneratorService;

class DocumentController extends Controller
{
    protected $docService;

    public function __construct()
    {
        $this->docService = new DocumentGeneratorService();
    }

    public function ruralActivity($id)
    {
        return (new GenerateRuralActivityAction($this->docService))->execute($id);
    }

    public function auto_Dec($id)
    {
        return (new GenerateAutoDeclarationAction($this->docService))->execute($id);
    }

    public function president_Dec($id)
    {
        return (new GeneratePresidentDeclarationAction($this->docService))->execute($id);
    }

    public function insurance_Auth($id)
    {
        return (new GenerateInsuranceAuthorizationAction($this->docService))->execute($id);
    }

    public function previdence_Auth($id)
    {
        return (new GeneratePrevidenceAuthorizationAction($this->docService))->execute($id);
    }

    public function licence_Requirement($id)
    {
        return (new GenerateLicenceRequirementAction($this->docService))->execute($id);
    }

    public function non_Literate_Affiliation($id)
    {
        return (new GenerateNonLiterateAffiliationAction($this->docService))->execute($id);
    }

    public function residence_Dec($id)
    {
        return (new GenerateResidenceDeclarationAction($this->docService))->execute($id);
    }

    public function affiliation_Dec($id)
    {
        return (new GenerateAffiliationDeclarationAction($this->docService))->execute($id);
    }

    public function registration_Form($id)
    {
        return (new GenerateRegistrationFormAction($this->docService))->execute($id);
    }

    public function seccond_Via_Reciept($id)
    {
        return (new GenerateSecondViaReceiptAction($this->docService))->execute($id);
    }

    public function social_Security_Guide($id)
    {
        return (new GenerateSocialSecurityGuideAction($this->docService))->execute($id);
    }

    public function INSS_Representation_Term($id)
    {
        return (new GenerateINSSRepresentationAction($this->docService))->execute($id);
    }

    public function dissemination($id)
    {
        return (new GenerateDisseminationAction($this->docService))->execute($id);
    }

    public function dec_Income($id)
    {
        return (new GenerateIncomeDeclarationAction($this->docService))->execute($id);
    }

    public function dec_Own_Residence($id)
    {
        return (new GenerateOwnResidenceAction($this->docService))->execute($id);
    }

    public function dec_Third_Residence($id)
    {
        return (new GenerateThirdResidenceAction($this->docService))->execute($id);
    }

    public function dec_New_Residence($id)
    {
        return (new GenerateNewResidenceAction($this->docService))->execute($id);
    }

    public function seccond_Check($id)
    {
        return (new GenerateSecondCheckAction($this->docService))->execute($id);
    }

    public function PIS($id)
    {
        return (new GeneratePISAction($this->docService))->execute($id);
    }
}
