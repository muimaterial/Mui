namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;

class EntityAuditService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Generates a string summary of changes for an entity and its direct relations.
     */
    public function generateChangeSummary(object $entity): string
    {
        $uow = $this->entityManager->getUnitOfWork();
        $uow->computeChangeSets(); // Ensure the changeset is up to date

        $summary = [];
        
        // 1. Track Main Entity Changes
        $entityChanges = $uow->getEntityChangeSet($entity);
        if (!empty($entityChanges)) {
            $summary[] = "[Main Entity: " . get_class($entity) . "]\n" . $this->formatChanges($entityChanges);
        }

        // 2. Track Direct Relations (One-to-One, Many-to-One)
        $classMetadata = $this->entityManager->getClassMetadata(get_class($entity));
        foreach ($classMetadata->getAssociationMappings() as $fieldName => $mapping) {
            $relatedObject = $classMetadata->getFieldValue($entity, $fieldName);

            if ($relatedObject instanceof PersistentCollection) {
                // For To-Many relations, we check for additions/removals
                $colChanges = $this->getCollectionChanges($relatedObject);
                if ($colChanges) {
                    $summary[] = "[Relation: $fieldName (Collection)]\n$colChanges";
                }
            } elseif (is_object($relatedObject) && $uow->isScheduledForUpdate($relatedObject)) {
                // For To-One relations, track field edits within that object
                $relatedChanges = $uow->getEntityChangeSet($relatedObject);
                if (!empty($relatedChanges)) {
                    $summary[] = "[Relation: $fieldName]\n" . $this->formatChanges($relatedChanges);
                }
            }
        }

        return implode("\n\n", $summary);
    }

    private function formatChanges(array $changeSet): string
    {
        $lines = [];
        foreach ($changeSet as $field => $values) {
            [$old, $new] = $values;
            
            // Format values for string storage (handling datetimes or objects)
            $oldStr = $this->valueToString($old);
            $newStr = $this->valueToString($new);

            $lines[] = "- $field: $oldStr -> $newStr";
        }
        return implode("\n", $lines);
    }

    private function getCollectionChanges(PersistentCollection $collection): ?string
    {
        $added = count($collection->getInsertDiff());
        $removed = count($collection->getDeleteDiff());

        if ($added === 0 && $removed === 0) {
            return null;
        }

        return "Added: $added items, Removed: $removed items";
    }

    private function valueToString(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) return $value->format('Y-m-d H:i:s');
        if (is_object($value)) return method_exists($value, '__toString') ? (string)$value : get_class($value);
        if (is_array($value)) return json_encode($value);
        if (is_bool($value)) return $value ? 'true' : 'false';
        return (string)($value ?? 'NULL');
    }
}

public function edit(Request $request, MyEntity $entity, EntityAuditService $auditService): Response
{
    // ... form handling ...
    
    if ($form->isSubmitted() && $form->isValid()) {
        $changeLog = $auditService->generateChangeSummary($entity);
        
        $auditEntry = new AuditLog();
        $auditEntry->setDetails($changeLog);
        
        $this->entityManager->persist($auditEntry);
        $this->entityManager->flush();
    }
}
