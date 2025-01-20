<?php

namespace App\Controller;

use App\Entity\Asset;
use App\Form\AssetType;
use App\Repository\AssetRepository;
use App\Service\CoinMarketCapService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/asset')]
final class AssetController extends AbstractController
{
    private CoinMarketCapService $coinMarketCapService;

    public function __construct(CoinMarketCapService $coinMarketCapService, LoggerInterface $logger)
    {
        $this->coinMarketCapService = $coinMarketCapService;
        $this->logger = $logger;
    }

    #[Route(name: 'app_asset_index', methods: ['GET'])]
    public function index(AssetRepository $assetRepository, EntityManagerInterface $entityManager): Response
    {
        try {
            $assetsData = $this->getAssetsInUSD($assetRepository, $entityManager);
        } catch (\Exception $e) {
//             Fallback: Use the values from the database (no API data)
            $this->logger->error('API call failed: ' . $e->getMessage());

            $user = $this->getUser();
            $assets = $assetRepository->findBy(['user' => $user]);

            $totalValueInUSD = 0;
            foreach ($assets as $asset) {
                $totalValueInUSD += $asset->getValueInUSD();
            }

            $assetsData = [
                'assets' => $assets,
                'totalValueInUSD' => $totalValueInUSD,
            ];
        }

        return $this->render('asset/index.html.twig', [
            'assets' => $assetsData['assets'],
            'totalValueInUSD' => $assetsData['totalValueInUSD'] ?? '',
        ]);
    }

    #[Route('/new', name: 'app_asset_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $asset = new Asset();
        $form = $this->createForm(AssetType::class, $asset);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $asset->setUser($this->getUser());
            $entityManager->persist($asset);
            $entityManager->flush();

            return $this->redirectToRoute('app_asset_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('asset/new.html.twig', [
            'asset' => $asset,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_asset_show', methods: ['GET'])]
    public function show(Asset $asset): Response
    {
        return $this->render('asset/show.html.twig', [
            'asset' => $asset,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_asset_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Asset $asset, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AssetType::class, $asset);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_asset_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('asset/edit.html.twig', [
            'asset' => $asset,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_asset_delete', methods: ['POST'])]
    public function delete(Request $request, Asset $asset, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$asset->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($asset);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_asset_index', [], Response::HTTP_SEE_OTHER);
    }

    public function getAssetsInUSD(AssetRepository $assetRepository, EntityManagerInterface $entityManager): array
    {
        $user = $this->getUser();
        $assets = $assetRepository->findBy(['user' => $user]);
        $totalValueInUSD = 0;
        $prices = $this->coinMarketCapService->getCryptoPrices();
        if (empty($prices)) {
            throw new \Exception("Failed to fetch prices from the API.");
        }

        foreach ($assets as $asset) {
            $currency = $asset->getCurrency();
            $amount = $asset->getAmount();

            if (in_array($currency, ['BTC', 'ETH', 'IOTA']) && $amount > 0) {
                if (isset($prices[$currency])) {
                    $assetValueInUSD = $amount * $prices[$currency];
                    $totalValueInUSD += $assetValueInUSD;

                    $asset->setValueInUSD($assetValueInUSD);

                }
            }
        }
        $entityManager->flush();

        return [
            'totalValueInUSD' => $totalValueInUSD,
            'assets' => $assets,
        ];
    }
}
