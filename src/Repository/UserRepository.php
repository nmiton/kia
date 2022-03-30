<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(User $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(User $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }


    /**
    * @return User[] Returns an array of User objects
    */
    public function findAllUserByScoreDesc(){
        return $this->createQueryBuilder('u')
            ->andWhere('u.isVerified = 1')
            ->andWhere('u.score IS NOT NULL')
            ->andWhere('u.score != 0')
            ->orderBy('u.score', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAnimalIsAliveWithLifeByUserId($userId)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
        SELECT a.id, a.animal_type_id, a.name, a.last_active, a.created_at, ac.value
            FROM animal as a
            INNER JOIN user u ON a.user_id = u.id
            INNER JOIN animal_caracteristic ac ON ac.animal_id = a.id 
            INNER JOIN caracteristic c ON c.id = ac.caracteristic_id
            WHERE user_id = :userId
            AND c.name LIKE "Vie"
            AND is_alive = 1
            GROUP BY c.name
            ';
        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery(['userId' => $userId]);

        // returns an array of arrays (i.e. a raw data set)
        return $resultSet->fetchAllAssociative();
    }

    
    public function findAnimalIsAliveByUserId($userId)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
        SELECT a.id, a.animal_type_id, a.name, a.last_active, a.created_at
            FROM animal as a
            INNER JOIN user u ON a.user_id = u.id
            WHERE user_id = :userId
            AND is_alive = 1
            ';
        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery(['userId' => $userId]);

        // returns an array of arrays (i.e. a raw data set)
        return $resultSet->fetchAllAssociative();
    }


    public function findAnimalStatsIsAliveByUser($userId)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT a.id, ac.value,c.name 
            FROM user u
            INNER JOIN animal a  ON a.user_id = u.id
            INNER JOIN animal_caracteristic ac ON ac.animal_id = a.id 
            INNER JOIN caracteristic c ON c.id = ac.caracteristic_id
            WHERE u.id = :userId
            AND a.is_alive = 1
            GROUP BY  c.id
            ';
        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery(['userId' => $userId]);

        // returns an array of arrays (i.e. a raw data set)
        return $resultSet->fetchAllAssociative();
    }

    // /**
    //  * @return User[] Returns an array of User objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
