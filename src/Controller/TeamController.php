<?php

namespace App\Controller;

use Pimcore\Controller\FrontendController;
use Pimcore\Model\DataObject\Team;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TeamController extends FrontendController
{
    /**
     * @Route("/teams", name="team_overview")
     */
    public function indexAction(Request $request): Response
    {
        $teamOverviews = array(
            \Pimcore\Model\DataObject\TeamOverview::getByPath('/Hogwarts Quidditch Teams'),
            \Pimcore\Model\DataObject\TeamOverview::getByPath('/International Quidditch Teams'),
        );

        return $this->render('team/index.html.twig', ['teamOverviews' => $teamOverviews]);
    }

    /**
     * @Route("/team/{id}", name="team_detail")
     */
    public function detailAction($id): Response
    {
        $team = Team::getById($id);

        return $this->render('team/team.html.twig', ['team' => $team]);
    }
}
