USE [simex08];
GO

SET NOCOUNT ON;
GO

BEGIN TRANSACTION;
GO

/*
    Maritime offer coverage seed

    Goals covered by this script:
    - Only maritime offers: tipus_transport_id = 1
    - Uses only ports and maritime lines
    - Covers all countries in paissos through origin/destination routing
    - Covers all estats_ofertes
    - Covers all incoterms
    - Covers all tipus_carrega
    - Covers all tipus_fluxes
    - Covers all tipus_validacions
    - Covers all tipus_contenidors

    Safe to rerun:
    - Previously inserted rows from this script are removed by comment prefix.
*/

DELETE FROM [dbo].[ofertes]
WHERE [comentaris] LIKE N'AUTO_MARITIME_COVERAGE:%';
GO

INSERT INTO [dbo].[ofertes] (
    [tipus_transport_id],
    [tipus_fluxe_id],
    [tipus_carrega_id],
    [incoterm_id],
    [client_id],
    [comentaris],
    [agent_comercial_id],
    [transportista_id],
    [pes_brut],
    [volum],
    [tipus_validacio_id],
    [port_origen_id],
    [port_desti_id],
    [aeroport_origen_id],
    [aeroport_desti_id],
    [linia_transport_maritim_id],
    [estat_oferta_id],
    [operador_id],
    [data_creacio],
    [data_validessa_inicial],
    [data_validessa_fina],
    [rao_rebuig],
    [tipus_contenidor_id]
)
VALUES
    (1, 1, 1, 1, 1,  N'AUTO_MARITIME_COVERAGE: Spain -> France | EXW | All cargo | Pending',                 4,  1, 12500.00, 28.50, 1,  1,  4, NULL, NULL, 1, 1, 3, '2026-04-10', '2026-04-10', '2026-05-10', NULL,                         1),
    (1, 2, 2, 2, 2,  N'AUTO_MARITIME_COVERAGE: France -> Italy | FCA | FCL | Accepted',                       4,  2, 18000.00, 33.20, 2,  3,  6, NULL, NULL, 2, 2, 3, '2026-04-10', '2026-04-11', '2026-05-11', NULL,                         2),
    (1, 3, 3, 3, 3,  N'AUTO_MARITIME_COVERAGE: Italy -> Germany | CPT | LCL | Rejected',                      4,  3,  9200.00, 19.40, 3,  5,  8, NULL, NULL, 3, 3, 3, '2026-04-10', '2026-04-12', '2026-05-12', N'Rate not approved',         3),
    (1, 4, 4, 4, 4,  N'AUTO_MARITIME_COVERAGE: Germany -> Portugal | CIP | Own Consolidation | Shipped',      4,  4, 14500.00, 26.70, 1,  7, 10, NULL, NULL, 4, 4, 3, '2026-04-10', '2026-04-13', '2026-05-13', NULL,                         4),
    (1, 2, 5, 5, 5,  N'AUTO_MARITIME_COVERAGE: Portugal -> United Kingdom | DAP | Project | Delayed',         4,  5, 27500.00, 48.10, 2,  9, 12, NULL, NULL, 5, 5, 3, '2026-04-10', '2026-04-14', '2026-05-14', N'Vessel rollover',          8),
    (1, 3, 6, 6, 6,  N'AUTO_MARITIME_COVERAGE: United Kingdom -> United States | DPU | LCL Groupage | Finalized', 4,  6,  7600.00, 17.80, 3, 11, 14, NULL, NULL, 6, 6, 3, '2026-04-10', '2026-04-15', '2026-05-15', NULL,                         6),
    (1, 4, 7, 7, 7,  N'AUTO_MARITIME_COVERAGE: United States -> Mexico | DDP | Full Container Load | In Transit', 4,  7, 21000.00, 36.90, 1, 13, 16, NULL, NULL, 7, 7, 3, '2026-04-10', '2026-04-16', '2026-05-16', NULL,                         7),
    (1, 1, 8, 8, 8,  N'AUTO_MARITIME_COVERAGE: Mexico -> Argentina | FAS | Breakbulk | Out for Delivery',     4,  8, 16800.00, 31.40, 2, 15, 18, NULL, NULL, 1, 8, 3, '2026-04-10', '2026-04-17', '2026-05-17', NULL,                         8),
    (1, 2, 9, 9, 9,  N'AUTO_MARITIME_COVERAGE: Argentina -> Brazil | FOB | Standard Air Cargo label | Pending', 4,  9,  5400.00, 11.20, 3, 17, 20, NULL, NULL, 2, 1, 3, '2026-04-10', '2026-04-18', '2026-05-18', NULL,                         9),
    (1, 3, 10, 10, 10, N'AUTO_MARITIME_COVERAGE: Brazil -> Japan | CFR | Dangerous Goods | Accepted',         4, 10, 13200.00, 24.60, 1, 19, 22, NULL, NULL, 3, 2, 3, '2026-04-10', '2026-04-19', '2026-05-19', NULL,                         1),
    (1, 4, 11, 11, 11, N'AUTO_MARITIME_COVERAGE: Japan -> China | CIF | Refrigerated | Finalized',            4, 11, 15400.00, 27.10, 2, 21, 24, NULL, NULL, 4, 6, 3, '2026-04-10', '2026-04-20', '2026-05-20', NULL,                         5),
    (1, 1, 2, 8, 12, N'AUTO_MARITIME_COVERAGE: China -> Canada | FAS | FCL | In Transit',                     4, 12, 19800.00, 35.00, 2, 23, 26, NULL, NULL, 5, 7, 3, '2026-04-10', '2026-04-21', '2026-05-21', NULL,                         4),
    (1, 2, 3, 9, 13, N'AUTO_MARITIME_COVERAGE: Canada -> Australia | FOB | LCL | Delayed',                    4,  1,  8800.00, 18.30, 3, 25, 28, NULL, NULL, 6, 5, 3, '2026-04-10', '2026-04-22', '2026-05-22', N'Congestion at transshipment', 2),
    (1, 3, 5, 10, 14, N'AUTO_MARITIME_COVERAGE: Australia -> India | CFR | Project | Shipped',                4,  2, 24600.00, 42.80, 1, 27, 30, NULL, NULL, 7, 4, 3, '2026-04-10', '2026-04-23', '2026-05-23', NULL,                         7),
    (1, 4, 11, 11, 15, N'AUTO_MARITIME_COVERAGE: India -> Spain | CIF | Refrigerated | Out for Delivery',     4,  3, 16100.00, 29.60, 2, 29,  2, NULL, NULL, 1, 8, 3, '2026-04-10', '2026-04-24', '2026-05-24', NULL,                         5);
GO

COMMIT TRANSACTION;
GO
